<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\AcquisitionAttribute;


use AppBundle\Entity\Event;
use AppBundle\Group\ChoiceOptionUsage;
use Doctrine\ORM\EntityRepository;

class AcquisitionAttributeRepository extends EntityRepository
{
    /**
     * Find one single events having acquisition attributes joined
     *
     * @param int $bid Id of desired @see Attribute
     * @return Attribute|null
     */
    public function findWithOptions(int $bid)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a', 'o')
           ->leftJoin('a.choiceOptions', 'o')
           ->andWhere($qb->expr()->eq('a.bid', ':bid'))
           ->setParameter('bid', $bid);
        $result = $qb->getQuery()->execute();
        if (count($result)) {
            $result = reset($result);
            return $result;
        }
        return null;
    }
    
    /**
     * Find list of all attributes used at event and using textual fillouts
     *
     * @param Event $event
     * @return array|Attribute[]
     */
    public function findTextualAttributesForEvent(Event $event): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
           ->innerJoin('a.events', 'e')
           ->andWhere('e.eid = :eid')
           ->setParameter('eid', $event->getEid())
           ->andWhere(
               $qb->expr()->orX(
                   'a.fieldType = :typeText',
                   'a.fieldType = :typeTextArea',
                   'a.fieldType = :typeNumber'
               )
           )
           ->setParameter('typeText', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
           ->setParameter('typeTextArea', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class)
           ->setParameter('typeNumber', \Symfony\Component\Form\Extension\Core\Type\NumberType::class);
        $result = $qb->getQuery()->execute();
        return $result;
    }
    
    /**
     * Find all unique fillout values for transmitted attributes
     *
     * @param array $attributes
     * @return array
     */
    public function findAllAttributeValuesForFillouts(array $attributes): array
    {
        $bids = [];
        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $bids[] = $attribute->getBid();
        }
        if (!count($bids)) {
            return [];
        }
        $qb = $this->_em->getConnection()->createQueryBuilder();
        $qb->select('f.bid', 'f.value')
           ->from('acquisition_attribute_fillout', 'f')
           ->andWhere($qb->expr()->in('f.bid', $bids))
           ->andWhere('f.value IS NOT NULL')
           ->groupBy('f.bid', 'f.value');
        
        $result = [];
        $queryResult = $qb->execute();
        while ($row = $queryResult->fetch()) {
            $result['acq_field_'.$row['bid']][] = $row['value'];
        }
        return $result;
    }
    
    /**
     * Find all @see Attributes with their options having formula enabled not deleted
     *
     * @return array|Attribute[]
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function findAllWithFormulaAndOptions(): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a', 'o')
           ->indexBy('a', 'a.bid')
           ->leftJoin('a.choiceOptions', 'o')
           ->andWhere('a.isPriceFormulaEnabled = 1')
           ->andWhere('a.deletedAt IS NULL');
        
        $result = $qb->getQuery()->execute();
        return $result;
    }
    
    /**
     * Fetch amount of occurrence of @see ChoiceFilloutValue
     *
     * @param Event $event
     * @param AttributeChoiceOption $choiceOption
     * @return ChoiceOptionUsage
     */
    public function fetchAttributeChoiceUsage(Event $event, AttributeChoiceOption $choiceOption): ChoiceOptionUsage
    {
        return new ChoiceOptionUsage($this->getEntityManager(), $event, $choiceOption);
    }
}
