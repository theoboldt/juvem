<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\AcquisitionAttribute\Variable;


use AppBundle\Entity\Event;
use Doctrine\ORM\EntityRepository;

class VariableRepository extends EntityRepository
{
    
    /**
     * Find all not deleted
     *
     * @return array|EventSpecificVariable[]
     */
    public function findAllNotDeleted(): array
    {
        return $this->findBy(['deletedAt' => null]);
    }
    
    /**
     * Find all variable values for transmitted {@see Event}
     *
     * @param Event $event
     * @return array|EventSpecificVariableValue[]
     */
    public function findAllValuesForEvent(Event $event): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('value', 'variable')
           ->from(EventSpecificVariableValue::class, 'value')
           ->leftJoin('value.variable', 'variable')
           ->andWhere($qb->expr()->eq('value.event', $event->getEid()))
           ->orderBy('variable.description', 'ASC');
        
        $result = [];
        /** @var EventSpecificVariableValue $value */
        foreach ($qb->getQuery()->execute() as $value) {
            $result[$value->getVariable()->getId()] = $value;
        }
        
        return $result;
    }
    
    
}
