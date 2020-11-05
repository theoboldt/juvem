<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Group;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\GroupFilloutValue;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;

class AttributeChoiceOptionUsageDistribution
{
    
    /**
     * EntityManager
     *
     * @var EntityManager
     */
    private $em;
    
    /**
     * Related attribute
     *
     * @var Attribute
     */
    private $attribute;
    
    /**
     * Related event
     *
     * @var Event
     */
    private $event;
    
    /**
     * distribution
     *
     * @var null
     */
    private $distribution = null;
    
    /**
     * ChoiceOptionUsage constructor.
     *
     * @param EntityManager $em    Entity manager
     * @param Event $event         Related event
     * @param Attribute $attribute Related attribute
     */
    public function __construct(
        EntityManager $em,
        Event $event,
        Attribute $attribute
    )
    {
        $this->em        = $em;
        $this->event     = $event;
        $this->attribute = $attribute;
    }
    
    /**
     * Fetch counts for usage from database
     */
    private function ensureFetched()
    {
        if ($this->distribution !== null) {
            return;
        }
        
        $bid = $this->attribute->getBid();
        $qb  = $this->em->createQueryBuilder();
        $qb->select(
            [
                'p.pid AS pid',
                'a.aid AS aid',
                'g.gid AS gid',
                'f.value AS value',
            ]
        )
           ->from(Fillout::class, 'f')
           ->leftJoin('f.participation', 'p')
           ->leftJoin('f.participant', 'a')
           ->leftJoin('a.participation', 'ap')
           ->leftJoin('f.employee', 'g')
           ->andWhere($qb->expr()->eq('f.attribute', $bid));
        
        $qb->andWhere(
            $qb->expr()->orX(
                'p.pid IS NULL',
                $qb->expr()->eq('p.event', $this->event->getEid())
            
            )
        );
        $qb->andWhere(
            $qb->expr()->orX(
                'a.aid IS NULL',
                $qb->expr()->eq('ap.event', $this->event->getEid())
            
            )
        );
        $qb->andWhere(
            $qb->expr()->orX(
                'g.gid IS NULL',
                $qb->expr()->eq('g.event', $this->event->getEid())
            
            )
        );
        $qb->andWhere('f.value is NOT NULL AND f.value <> \'\'');
        
        $query = $qb->getQuery();
        
        $this->distribution = [];
        foreach ($query->execute() as $row) {
            if (empty($row['value'])) {
                continue;
            }
            $value = Fillout::convertRawValueForField($this->attribute, $row['value']);
            if (!$value instanceof GroupFilloutValue) {
                throw new \InvalidArgumentException('Unexpected value type occured');
            }
            $groupId = $value->getGroupId();
            if (!isset($this->distribution[$groupId])) {
                /** @var AttributeChoiceOption $choiceOption */
                $choice = null;
                foreach ($this->attribute->getChoiceOptions() as $choiceOption) {
                    if ($groupId === $choiceOption->getId()) {
                        $choice = $choiceOption;
                        break;
                    }
                }
                if (!$choice) {
                    throw new \InvalidArgumentException('Required choice option with id "' . $groupId . '" not found');
                }
                $this->distribution[$groupId] = new FetchedChoiceOptionUsage($choice);
            }
            /** @var FetchedChoiceOptionUsage $usage */
            $usage = $this->distribution[$groupId];
            
            if ($row['pid']) {
                $usage->addParticipationId($row['pid']);
            }
            if ($row['aid']) {
                $usage->addParticipantId($row['aid']);
            }
            if ($row['gid']) {
                $usage->addEmployeeId($row['gid']);
            }
        }
    
        foreach ($this->attribute->getChoiceOptions() as $choiceOption) {
            $choiceOptionId = $choiceOption->getId();
            if (!isset($this->distribution[$choiceOptionId])) {
                $this->distribution[$choiceOptionId] = new FetchedChoiceOptionUsage($choiceOption);
            }
        }
        
        /** @var FetchedChoiceOptionUsage $usage */
        foreach ($this->distribution as $usage) {
            $usage->freeze();
        }
    }
    
    /**
     * Get full distribution details
     *
     * @return array|ChoiceOptionUsageInterface[]
     */
    public function getDistribution(): array
    {
        $this->ensureFetched();
        return $this->distribution;
    }
    
    /**
     * Get distribution for transmitted option
     *
     * @param AttributeChoiceOption $choiceOption
     * @return ChoiceOptionUsageInterface
     */
    public function getOptionDistribution(AttributeChoiceOption $choiceOption): ChoiceOptionUsageInterface
    {
        $this->ensureFetched();
        if (!isset($this->distribution[$choiceOption->getId()])) {
            $usage = new FetchedChoiceOptionUsage($choiceOption);
            $usage->freeze();
            $this->distribution[$choiceOption->getId()] = $usage;
        }
        return $this->distribution[$choiceOption->getId()];
    }
}
