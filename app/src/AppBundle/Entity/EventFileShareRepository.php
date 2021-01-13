<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EmployeeRepository
 */
class EventFileShareRepository extends EntityRepository
{
    
    /**
     * Fetch all for transmitted event
     *
     * @param Event $event The event
     * @return EventFileShare[]
     */
    public function findForEvent(Event $event)
    {
        $eid = $event->getEid();
        
        $qb = $this->createQueryBuilder('s')
                   ->select(['s'])
                   ->andWhere('s.event = :eid');
        
        $qb->setParameter('eid', $eid);
        
        $query = $qb->getQuery();
        
        return $query->execute();
    }
    
    /**
     * Fetch for single purpose for for transmitted event if available
     *
     * @param Event $event The event
     * @param string $purpose
     * @return EventFileShare|null
     */
    public function findSinglePurposeForEvent(Event $event, string $purpose): ?EventFileShare
    {
        $eid = $event->getEid();
        
        $qb = $this->createQueryBuilder('s')
                   ->select(['s'])
                   ->andWhere('s.event = :eid')
        ->andWhere('s.purpose = :purpose');
        
        $qb->setParameter('eid', $eid);
        $qb->setParameter('purpose', $purpose);
        
        $query = $qb->getQuery();
        
        $result = $query->execute();
        
        return count($result) ? $result[0] : null;
    }
}
