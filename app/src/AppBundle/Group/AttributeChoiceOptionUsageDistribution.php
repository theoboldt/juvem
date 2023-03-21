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
use AppBundle\Entity\CustomField\CustomFieldValueCollection;
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\GroupCustomFieldValue;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;

class AttributeChoiceOptionUsageDistribution
{

    /**
     * EntityManager
     *
     * @var EntityManagerInterface
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
     * @param EntityManagerInterface $em        Entity manager
     * @param Event                  $event     Related event
     * @param Attribute              $attribute Related attribute
     */
    public function __construct(
        EntityManagerInterface $em,
        Event                  $event,
        Attribute              $attribute
    ) {
        $this->em        = $em;
        $this->event     = $event;
        $this->attribute = $attribute;
    }

    private function processEntityUsageCount(string $relatedClass): void
    {
        $bid = $this->attribute->getBid();

        $qb = $this->em->createQueryBuilder();

        switch ($relatedClass) {
            case Participation::class:
                $qb->select(['e.pid AS id', 'e.customFieldValues AS customFieldValues']);
                $qb->from($relatedClass, 'e')
                   ->andWhere($qb->expr()->eq('e.event', $this->event->getEid()));
                break;
            case Participant::class:
                $qb->select(['a.aid AS id', 'a.customFieldValues AS customFieldValues']);
                $qb->from(Participation::class, 'p')
                   ->innerJoin('p.participants', 'a')
                   ->andWhere($qb->expr()->eq('p.event', $this->event->getEid()));
                break;
            case Employee::class:
                $qb->select(['e.gid AS id', 'e.customFieldValues AS customFieldValues']);
                $qb->from($relatedClass, 'e')
                   ->andWhere($qb->expr()->eq('e.event', $this->event->getEid()));
                break;
            default:
                throw new \InvalidArgumentException('Unknown class ' . $relatedClass . ' transmitted');
        }

        $query = $qb->getQuery();
        foreach ($query->execute() as $row) {
            if (is_array($row['customFieldValues'])) {
                $customFieldCollectionValues = [];
                foreach ($row['customFieldValues'] as $customFieldValueData) {
                    $customFieldCollectionValues[] = CustomFieldValueContainer::createFromArray(
                        $customFieldValueData
                    );
                }
                $customFieldValueCollection = new CustomFieldValueCollection($customFieldCollectionValues);
                $customFieldValueContainer  = $customFieldValueCollection->get($bid);
                if (!$customFieldValueContainer) {
                    continue;
                }
                $customFieldValue = $customFieldValueContainer->getValue();
                if ($customFieldValue instanceof GroupCustomFieldValue) {
                    $groupId = $customFieldValue->getValue();
                    if (!$groupId) {
                        continue;
                    }
                    
                    /** @var FetchedChoiceOptionUsage $usage */
                    $usage = $this->distribution[$groupId];

                    switch ($relatedClass) {
                        case Participation::class:
                            $usage->addParticipationId($row['id']);
                            break;
                        case Participant::class:
                            $usage->addParticipantId($row['id']);
                            break;
                        case Employee::class:
                            $usage->addEmployeeId($row['id']);
                            break;
                        default:
                            throw new \InvalidArgumentException('Unknown class ' . $relatedClass . ' transmitted');
                    }
                }
            }
        }
    }

    /**
     * Fetch counts for usage from database
     */
    private function ensureFetched()
    {
        if ($this->distribution !== null) {
            return;
        }
        $this->distribution = [];

        foreach ($this->attribute->getChoiceOptions() as $choiceOption) {
            $choiceOptionId = $choiceOption->getId();
            if (!isset($this->distribution[$choiceOptionId])) {
                $this->distribution[$choiceOptionId] = new FetchedChoiceOptionUsage($choiceOption);
            }
        }

        if ($this->attribute->getUseAtParticipation()) {
            $this->processEntityUsageCount(Participation::class);
        }
        if ($this->attribute->getUseAtParticipant()) {
            $this->processEntityUsageCount(Participant::class);
        }
        if ($this->attribute->getUseAtEmployee()) {
            $this->processEntityUsageCount(Employee::class);
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
