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
}
