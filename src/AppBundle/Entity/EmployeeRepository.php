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
class EmployeeRepository extends EntityRepository
{
    
    /**
     * Fetch all events ordered by title
     *
     * @param   Event $event The event
     * @param   bool $includeDeleted Set to true to include deleted participants
     * @return array
     */
    public function findForEvent(Event $event, $includeDeleted = false)
    {
        $eid = $event->getEid();
        
        $qb = $this->createQueryBuilder('g')
                   ->select(['g', 'f', 'a', 'pn'])
                   ->andWhere('g.event = :eid')
                   ->leftJoin('g.acquisitionAttributeFillouts', 'f')
                   ->leftJoin('f.attribute', 'a')
                   ->leftJoin('g.phoneNumbers', 'pn')
                   ->orderBy('g.nameFirst, g.nameLast', 'ASC');
        
        if (!$includeDeleted) {
            $qb->andWhere('g.deletedAt IS NULL');
        }
        
        $qb->setParameter('eid', $eid);
        
        $query = $qb->getQuery();
        
        return $query->execute();
        
    }
    
}
