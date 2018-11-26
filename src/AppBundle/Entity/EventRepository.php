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

use AppBundle\BitMask\ParticipantStatus;
use Doctrine\ORM\EntityRepository;
use PDO;

/**
 * EventRepository
 */
class EventRepository extends EntityRepository
{
    /**
     * Amount of days per year
     */
    const DAYS_OF_YEAR = 365;

    /**
     * Find one single events having participations joined
     *
     * @param int $eid Id of desired event
     * @return Event|null
     */
    public function findWithAcquisitionAttributes(int $eid)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e', 'a')
           ->leftJoin('e.acquisitionAttributes', 'a')
           ->andWhere($qb->expr()->eq('e.eid', ':eid'))
           ->setParameter('eid', $eid);
        $result = $qb->getQuery()->execute();
        if (count($result)) {
            $result = reset($result);
            return $result;
        }
        return null;
    }
    /**
     * Find one single events having participations joined
     *
     * @param int $eid Id of desired event
     * @return Event|null
     */
    public function findWithParticipations(int $eid)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e', 'p')
           ->leftJoin('e.participations', 'p')
           ->addOrderBy('p.nameLast')
           ->addOrderBy('p.nameFirst')
           ->andWhere($qb->expr()->eq('e.eid', ':eid'))
           ->setParameter('eid', $eid);
        $result = $qb->getQuery()->execute();
        if (count($result)) {
            $result = reset($result);
            return $result;
        }
        return null;
    }

    /**
     * Find one single events having participants joined
     *
     * @param int $eid Id of desired event
     * @return Event|null
     */
    public function findWithParticipants(int $eid)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e', 'p', 'a')
           ->leftJoin('e.participations', 'p')
           ->leftJoin('p.participants', 'a')
           ->addOrderBy('p.nameLast')
           ->addOrderBy('p.nameFirst')
           ->addOrderBy('a.nameLast')
           ->addOrderBy('a.nameFirst')
           ->andWhere($qb->expr()->eq('e.eid', ':eid'))
           ->setParameter('eid', $eid);
        $result = $qb->getQuery()->execute();
        if (count($result)) {
            $result = reset($result);
            return $result;
        }
        return null;
    }
    /**
     * Find one single events having participants joined
     *
     * @param int $eid Id of desired event
     * @return Event|null
     */
    public function findWithUserAssignments(int $eid)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e', 'a', 'u')
           ->leftJoin('e.userAssignments', 'a')
           ->leftJoin('a.user', 'u')
           ->addOrderBy('u.nameLast')
           ->addOrderBy('u.nameFirst')
           ->andWhere($qb->expr()->eq('e.eid', ':eid'))
           ->setParameter('eid', $eid);
        $result = $qb->getQuery()->execute();
        if (count($result)) {
            $result = reset($result);
            return $result;
        }
        return null;
    }

    /**
     * Fetch all events ordered by title, including participants count data
     *
     * @param bool      $includeDeleted   Set to true to include deleted events in result
     * @param bool      $includeInvisible Set to true to include invisible events
     * @param User|null $filterForUser    If set, result is filtered by events the current user is assigned to
     * @return array|Event[]
     */
    public function findAllWithCounts($includeDeleted = false, $includeInvisible = false, User $filterForUser = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select(
            'e AS eventEntity',
            sprintf(
                'SUM(CASE WHEN (a1.deletedAt IS NULL 
                             AND BIT_AND(a1.status, %2$d) != %2$d 
                             AND BIT_AND(a1.status, %3$d) != %3$d 
                             AND BIT_AND(a1.status, %1$d) = %1$d) THEN 1 ELSE 0 END) AS participants_count_confirmed',
                ParticipantStatus::TYPE_STATUS_CONFIRMED,
                ParticipantStatus::TYPE_STATUS_WITHDRAWN,
                ParticipantStatus::TYPE_STATUS_REJECTED
            ),
            sprintf(
                'SUM(CASE WHEN (a1.deletedAt IS NULL
                            AND BIT_AND(a1.status, %1$d) != %1$d 
                            AND BIT_AND(a1.status, %2$d) != %2$d) THEN 1 ELSE 0 END) AS participants_count',
                ParticipantStatus::TYPE_STATUS_WITHDRAWN,
                ParticipantStatus::TYPE_STATUS_REJECTED
            )
        )
           ->from(Event::class, 'e')
           ->leftJoin('e.participations', 'p')
           ->leftJoin('p.participants', 'a1')
           ->groupBy('e.eid');

        if (!$includeDeleted) {
            $qb->andWhere('e.deletedAt IS NULL');
        }
        if (!$includeInvisible) {
            $qb->andWhere('e.isVisible = 1');
        }
        if ($filterForUser) {
            $qb->innerJoin('e.userAssignments', 'ua')
               ->andWhere($qb->expr()->eq('ua.user', $filterForUser->getUid()));
        }

        $qb->orderBy('e.startDate, e.startTime, e.title', 'ASC');

        $query = $qb->getQuery();

        $result = array();
        foreach ($query->execute() as $row) {
            $event = $row['eventEntity'];
            /** @var Event $event */
            $event->setParticipantsCounts((int)$row['participants_count']);
            $event->setParticipantsConfirmedCount((int)$row['participants_count_confirmed']);
            $result[] = $event;
        }

        return $result;
    }

    /**
     * Fetch all events ordered by title
     *
     * @param bool $excludeDeleted  Set to true to exclude deleted @see Event s
     * @return array
     */
    public function findAllOrderedByTitle($excludeDeleted = false)
    {
         $qb = $this->createQueryBuilder('e')
                   ->orderBy('e.title', 'ASC');
         if ($excludeDeleted) {
             $qb->andWhere('e.deletedAt IS NULL');
         }
        return $qb->getQuery()->execute();
    }

    /**
     * Fetch all events ordered by title
     *
     * @return array
     */
    public function findEidListFutureEvents()
    {
        return $this->getEntityManager()
                    ->getConnection()
                    ->executeQuery('SELECT eid FROM event WHERE start_date >= NOW()')
                    ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Fetch all events ordered by title
     *
     * @return array
     */
    public function findEidListPastEvents()
    {
        return $this->getEntityManager()
                    ->getConnection()
                    ->executeQuery('SELECT eid FROM event WHERE start_date < NOW()')
                    ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Find list of events, subscribing users included
     *
     * @return Event[]
     */
    public function findWithSubscriptions() {
         $qb = $this->createQueryBuilder('e')
                   ->select('e', 's')
                   ->innerJoin('e.subscribers', 's')
                   ->orderBy('e.title', 'ASC');
        return $qb->getQuery()->execute();
    }


    /**
     * Get the total amount of an events participants who are not deleted or whose participation is withdrawn or rejected
     *
     * @param Event $event
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function participantsCount(Event $event)
    {
        $eid = $event->getEid();
        /** @var \DateTime $start */
        $query = sprintf(
            'SELECT COUNT(*)
               FROM participant a, participation p
              WHERE a.pid = p.pid
                AND a.deleted_at IS NULL
                AND (a.status & %1$d) != %1$d
                AND (a.status & %2$d) != %2$d
                AND p.eid = ?',
            ParticipantStatus::TYPE_STATUS_WITHDRAWN,
            ParticipantStatus::TYPE_STATUS_REJECTED
        );

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($eid));

        return $stmt->fetchColumn();
    }

    /**
     * Get the total amount of an @see Employee related to transmitted @see Event
     *
     * @param Event $event
     * @return bool|string
     */
    public function employeeCount(Event $event)
    {
        $eid = $event->getEid();
        /** @var \DateTime $start */
        $query
            = 'SELECT COUNT(*)
               FROM employee e
              WHERE e.eid = ?
                AND e.deleted_at IS NULL';

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute([$eid]);

        return $stmt->fetchColumn();
    }

    /**
     * Get a list of years of life of the participants who are not deleted or whose participation is withdrawn or rejected
     *
     * @param Event $event
     * @return array|int[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function participantsYearsOfLifeList(Event $event)
    {
        $eid = $event->getEid();
        /** @var \DateTime $start */
        $start = $event->getStartDate();

        $query = sprintf(
            'SELECT a.birthday
               FROM participant a, participation p
              WHERE a.pid = p.pid
                AND a.deleted_at IS NULL
                AND (a.status & %1$d) != %1$d
                AND (a.status & %2$d) != %2$d
                AND p.eid = ?',
            ParticipantStatus::TYPE_STATUS_WITHDRAWN,
            ParticipantStatus::TYPE_STATUS_REJECTED
        );

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($eid));

        $birthdayList = $stmt->fetchAll();

        $ageList = array();
        foreach ($birthdayList as $participant) {
            $ageList[] = self::yearsOfLife(new \DateTime($participant['birthday']), $start);
        }

        return $ageList;
    }

    /**
     * Get the age distribution of an event of participants who are not deleted or whose participation is withdrawn
     *
     * @param Event $event
     * @return array
     */
    public function participantsAgeDistribution(Event $event)
    {
        $ageList = $this->participantsYearsOfLifeList($event);

        $ageDistribution = array();
        foreach ($ageList as $age) {
            if (!isset($ageDistribution[$age])) {
                $ageDistribution[$age] = 0;
            }
            ++$ageDistribution[$age];
        }

        ksort($ageDistribution);
        return $ageDistribution;
    }

    /**
     * Fetch gender distribution of an event of participants who are not deleted or whose participation is withdrawn
     *
     * @param Event $event
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function participantsGenderDistribution(Event $event)
    {
        $eid   = $event->getEid();
        $query = sprintf(
            'SELECT gender, COUNT(*) AS count
               FROM participant a, participation p
              WHERE a.pid = p.pid
                AND a.deleted_at IS NULL
                AND (a.status & %1$d) != %1$d
                AND (a.status & %2$d) != %2$d
                AND p.eid = ?
           GROUP BY a.gender',
            ParticipantStatus::TYPE_STATUS_WITHDRAWN,
            ParticipantStatus::TYPE_STATUS_REJECTED
        );

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($eid));

        $genderDistribution = array();
        foreach ($stmt->fetchAll() as $distribution) {
            switch ($distribution['gender']) {
                case Participant::TYPE_GENDER_MALE:
                    $genderDistribution[Participant::TYPE_GENDER_MALE] = array(
                        'type'  => Participant::TYPE_GENDER_MALE,
                        'label' => Participant::LABEL_GENDER_MALE,
                        'count' => $distribution['count']
                    );
                    break;
                case Participant::TYPE_GENDER_FEMALE:
                    $genderDistribution[Participant::TYPE_GENDER_FEMALE] = array(
                        'type'  => Participant::TYPE_GENDER_FEMALE,
                        'label' => Participant::LABEL_GENDER_FEMALE,
                        'count' => $distribution['count']
                    );
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown gender type found');
            }
        }

        return $genderDistribution;
    }

    /**
     * Get last modified of any event
     *
     * @return \DateTime
     * @throws \Doctrine\DBAL\DBALException
     */
    public function lastModified()
    {
        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare('SELECT MAX(modified_at) FROM event');
        $stmt->execute();
        $lastModified = $stmt->fetchColumn();
        if (!$lastModified) {
            return new \DateTime('2017-01-01');
        }

        return new \DateTime($lastModified);
    }

    /**
     * Calculate an age
     *
     * @param \DateTime  $birthday  The birthday of the person which age should be calculated
     * @param \DateTime  $deadline  The date where the calculation is desired
     * @param  bool|null $precision If you want the result to be rounded with round(), specify precision here
     * @return float                Age in years
     */
    public static function age(\DateTime $birthday, \DateTime $deadline, $precision = null): float
    {
        $ageInDays  = $deadline->diff($birthday)
                               ->format('%a');
        $ageInYears = $ageInDays / self::DAYS_OF_YEAR;

        if ($precision) {
            return round($ageInYears, $precision);
        }
        return $ageInYears;
    }

    /**
     * Calculate amount of (completed) years of life at specific date
     *
     * @param \DateTime $birthday The birthday of the person which age should be calculated
     * @param \DateTime $deadline The date where the calculation is desired
     * @return int                Years of life in years
     */
    public static function yearsOfLife(\DateTime $birthday, \DateTime $deadline): int
    {
        $ageInYears = $deadline->diff($birthday)
                               ->format('%y');
        return (int)$ageInYears;
    }

    /**
     * Check if a birthday is celebrated in a given time span
     *
     * @param \DateTime      $birthday The birthday of the person which age should be calculated
     * @param \DateTime      $start    Begin of timespan in which the birthday may happen
     * @param \DateTime|null $end      End of timespan in which the birthday may happen. May be null if start and end
     *                                 is same
     * @return bool True if so
     */
    public static function hasBirthdayInTimespan(\DateTime $birthday, \DateTime $start, \DateTime $end = null)
    {
        $start = clone $start;
        $start->setTime(0, 0, 0);

        if (!$end) {
            $end = $start;
        }
        $end = clone $end;
        $end->setTime(23, 59, 59);

        $birthdayInStartYear = new \DateTime();
        $birthdayInStartYear->setDate(
            (int)$start->format('Y'), (int)$birthday->format('m'), (int)$birthday->format('d')
        );

        return ($birthdayInStartYear >= $start && $birthdayInStartYear <= $end);
    }
}
