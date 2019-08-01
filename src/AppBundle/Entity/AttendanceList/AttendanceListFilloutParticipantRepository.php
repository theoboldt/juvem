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

use AppBundle\Entity\Participant;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class AttendanceListFilloutParticipantRepository extends EntityRepository
{
    
    /**
     * @param int $columnId
     * @return AttendanceListColumn|null
     */
    public function findColumnById(int $columnId): ?AttendanceListColumn
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('c')
           ->from(AttendanceListColumn::class, 'c')
           ->where('c.columnId = :columnId')
           ->setParameter('columnId', $columnId);
        return $qb->getQuery()->getOneOrNullResult();
    }
    
    /**
     * Fetch fillouts by aid and tid
     *
     * @param Participant $participant Participant id
     * @param AttendanceList $list     Attendance List id
     * @param AttendanceListColumn $column
     * @param bool $createIfNotExists  Set to true to automatically create such an entry
     * @return AttendanceListParticipantFillout|null
     */
    public function findFillout(
        Participant $participant, AttendanceList $list, AttendanceListColumn $column, $createIfNotExists = false
    )
    {
        $fillout = $this->findOneBy(['participant' => $participant, 'attendanceList' => $list, 'column' => $column]);
        if (!$fillout) {
            if ($createIfNotExists) {
                $fillout = new AttendanceListParticipantFillout($list, $participant);
                $fillout->setParticipant($participant);
                $fillout->setAttendanceList($list);
            } else {
                return null;
            }
        }
        
        return $fillout;
    }
    
    public function processUpdates(AttendanceList $list, array $updates)
    {
        return $this->getEntityManager()->transactional(
            function (EntityManager $em) use ($list, $updates) {
                $query = 'INSERT INTO attendance_list_participant_fillout(list_id, participant_id, column_id, choice_id, created_at)
                               VALUES (:list_id, :participant_id, :column_id, :choice_id, NOW())
              ON DUPLICATE KEY UPDATE choice_id = :choice_id, modified_at = NOW()';
                
                $listId = $list->getTid();
                $qb     = $em->getConnection()->createQueryBuilder();
                $qb->select(['o.choice_id', 'c.column_id'])
                   ->from('attendance_list_column_choices', 'o')
                   ->innerJoin('o', 'attendance_list_column', 'c', 'c.column_id = o.column_id')
                   ->innerJoin('c', 'attendance_list_column_assignments', 's', 's.column_id = c.column_id')
                   ->andWhere($qb->expr()->eq('s.list_id', $list->getTid()));
                $result  = $qb->execute();
                $choices = [];
                while ($row = $result->fetch()) {
                    $choices[$row['column_id']][] = (int)$row['choice_id'];
                }
                
                foreach ($updates as $update) {
                    $qb = $em->createQueryBuilder();
                    $qb->select('a')
                       ->from(Participant::class, 'a')
                       ->innerJoin('a.participation', 'p')
                       ->andWhere('p.event = :event')
                       ->setParameter('event', $list->getEvent())
                       ->andWhere('a.aid = :aid')
                       ->setParameter('aid', $update['aid']);
                    $participant = $qb->getQuery()->getOneOrNullResult();
                    if ($participant === null) {
                        throw new \RuntimeException('Tried to update unavailable participant');
                    }
                    if (!isset($choices[$update['columnId']])) {
                        throw new \RuntimeException(
                            'Desired column ' . $update['columnId'] . ' is not available for list ' . $listId
                        );
                    }
                    if ($update['choiceId'] !== 0 && !in_array($update['choiceId'], $choices[$update['columnId']])) {
                        throw new \RuntimeException(
                            'Desired choice ' . $update['choiceId'] . ' is not available for  column ' .
                            $update['columnId'] . ' in list ' . $listId
                        );
                    }
                    if ($update['choiceId']) {
                        $em->getConnection()->executeQuery(
                            $query,
                            [
                                'list_id'        => $listId,
                                'participant_id' => $update['aid'],
                                'column_id'      => $update['columnId'],
                                'choice_id'      => $update['choiceId'],
                            ]
                        );
                    } else {
                        $em->getConnection()->executeQuery(
                            'DELETE FROM attendance_list_participant_fillout WHERE list_id = ? AND participant_id = ? AND column_id = ?',
                            [
                                $listId,
                                $update['aid'],
                                $update['columnId']
                            ]
                        );
                    }
                    
                }
                $em->flush();
            }
        );
        
    }
    
    /**
     * Fetch fillout overview for all participants for a list
     *
     * @param AttendanceList $list
     * @return array
     */
    public function fetchAttendanceListDataForList(AttendanceList $list)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('aid')
           ->from('participant', 'participant')
           ->innerJoin('participant', 'participation', 'participation', 'participation.pid = participant.pid')
           ->andWhere('participation.eid = :eid')
           ->setParameter('eid', $list->getEvent()->getEid());
        $aids = $qb->execute()->fetchAll(FetchMode::COLUMN, 0);
        
        $columns = [];
        foreach ($list->getColumns() as $column) {
            $columns[$column->getColumnId()] = [
                'choice_id'   => null,
                'comment'     => null,
                'created_at'  => null,
                'modified_at' => null,
            ];
        }
        
        $result = [];
        foreach ($aids as $aid) {
            $result[$aid] = [
                'columns' => $columns
            ];
        }
        
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select(
            [
                'fillout.participant_id',
                'columns.column_id',
                'fillout.choice_id',
                'fillout.comment',
                'fillout.modified_at',
                'fillout.created_at',
            ]
        )
           ->from('attendance_list_participant_fillout', 'fillout')
           ->innerJoin('fillout', 'attendance_list_column_choices', 'choices', 'fillout.choice_id = choices.choice_id')
           ->innerJoin('choices', 'attendance_list_column', 'columns', 'choices.column_id = columns.column_id')
           ->andWhere('fillout.list_id = :list_id')
           ->setParameter('list_id', $list->getTid());
        $queryResult = $qb->execute();
        while ($row = $queryResult->fetch()) {
            $result[$row['participant_id']]['columns'][$row['column_id']] = [
                'choice_id'   => (int)$row['choice_id'],
                'comment'     => empty(trim($row['comment'])) ? null : trim($row['comment']),
                'created_at'  => $row['created_at'],
                'modified_at' => $row['modified_at'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Fetch all by id
     *
     * @param array|int[] $tids
     * @return array|AttendanceList[]
     */
    public function findByIds(array $tids): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('attendance_list', 'event', 'columns', 'choices')
           ->from(AttendanceList::class, 'attendance_list')
            ->innerJoin('attendance_list.event', 'event')
            ->innerJoin('attendance_list.columns', 'columns')
            ->innerJoin('columns.choices', 'choices')
           ->where($qb->expr()->in('attendance_list.tid', $tids));
        
        return $qb->getQuery()->execute();
    }
    
}
