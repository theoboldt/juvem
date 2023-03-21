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
     * @param  Event            $event          The event
     * @param  array|int[]|null $filter         If defined, filters result by transmitted @see Employee gids
     * @param  bool             $includeDeleted Set to true to include deleted participants
     * @return array
     */
    public function findForEvent(Event $event, array $filter = null, $includeDeleted = false)
    {
        $eid = $event->getEid();

        $qb = $this->createQueryBuilder('g')
                   ->select(['g', 'e', 'a', 'pn'])
                   ->andWhere('g.event = :eid')
                   ->innerJoin('g.event', 'e')
                   ->leftJoin('e.acquisitionAttributes', 'a')
                   ->leftJoin('g.phoneNumbers', 'pn')
                   ->orderBy('g.nameFirst, g.nameLast', 'ASC');

        if (!$includeDeleted) {
            $qb->andWhere('g.deletedAt IS NULL');
        }

        $qb->setParameter('eid', $eid);

        if ($filter !== null) {
            $qb->andWhere("g.gid IN(:employeeList)")
               ->setParameter('employeeList', $filter);
        }


        $query = $qb->getQuery();

        return $query->execute();
    }
    
    /**
     * Find all employees by transmitted ids
     *
     * @param array|int[] $idList List of employee ids
     * @return array|Employee[]
     */
    public function findByIdList(array $idList): array
    {
        $qb = $this->createQueryBuilder('g')
                   ->select(['g', 'e', 'a', 'pn'])
                   ->innerJoin('g.event', 'e')
                   ->leftJoin('e.acquisitionAttributes', 'a')
                   ->leftJoin('g.phoneNumbers', 'pn')
                   ->orderBy('g.nameFirst, g.nameLast', 'ASC');
        
        $qb->andWhere("g.gid IN(:employeeList)")
           ->setParameter('employeeList', $idList);
        
        $query = $qb->getQuery();
        
        return $query->execute();
    }

    /**
     * Find related participants by comparing birthday (exact) and name (fuzzy)
     *
     * @param Employee $baseEmployee Employee for compare
     * @return array|Employee[]         Related participants
     */
    public function relatedEmployees(Employee $baseEmployee)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('e', 'g')
           ->from(Employee::class, 'g')
           ->innerJoin('g.event', 'e')
           ->orderBy('e.startDate', 'DESC');
        $query = $qb->getQuery();

        $gid       = $baseEmployee->getGid();
        $firstName = trim($baseEmployee->getNameFirst());
        $lastName  = trim($baseEmployee->getNameLast());

        $result = [];

        /** @var Employee $participant */
        foreach ($query->execute() as $participant) {
            if ($gid != $participant->getGid()
                && levenshtein($firstName, trim($participant->getNameFirst())) < 5
                && levenshtein($lastName, trim($participant->getNameLast())) < 5
            ) {
                $result[] = $participant;
            }
        }

        return $result;
    }
}
