<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\AttendanceList;

use Doctrine\ORM\EntityRepository;


class AttendanceListColumnRepository extends EntityRepository
{
    /**
     * Find all for a list with counts
     *
     * @return array
     */
    public function findAllForList()
    {
        $qb = $this->createQueryBuilder('column_entity', 'column_entity.columnId');
        $qb->select(['column_entity AS column', 'COUNT(choices) AS choice_count'])
           ->leftJoin('column_entity.choices', 'choices')
           ->groupBy('column_entity.columnId', 'column_entity.title');
        $resultChoiceCount = $qb->getQuery()->execute();
        
        $qb = $this->createQueryBuilder('column_entity', 'column_entity.columnId');
        $qb->select(['column_entity.columnId AS column_id', 'COUNT(lists) as list_count'])
           ->leftJoin('column_entity.lists', 'lists')
           ->groupBy('column_entity.columnId');
        $listCount = $qb->getQuery()->execute();
        
        $row = null;
        foreach ($resultChoiceCount as $columnId => &$row) {
            $row                 = array_merge($row, $listCount[$columnId]);
            $row['list_count']   = (int)$row['list_count'];
            $row['choice_count'] = (int)$row['choice_count'];
        }
        unset($row);
        return $resultChoiceCount;
    }
    
}