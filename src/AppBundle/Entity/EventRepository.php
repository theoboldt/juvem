<?php

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
     * Fetch all events ordered by title
     *
     * @return array
     */
    public function findAllOrderedByTitle()
    {
        return $this->getEntityManager()
                    ->createQuery(
                        'SELECT e FROM AppBundle:EVENT e ORDER BY e.title ASC'
                    )
                    ->getResult();
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
     * Get a list of participants of an event
     *
     * @param   Event      $event  The event
     * @param   null|array $filter Transmit a list of aids to filter out participants not included in list
     * @return  array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function participantsList(Event $event, array $filter = null)
    {
        $eid = $event->getEid();

        if ($filter === array()) {
            return array(); //empty result
        }

        $qb = $this->createQueryBuilder('AppBundle:Participant')
                   ->select('a, p, pn')
                   ->from('AppBundle:Participant', 'a')
                   ->innerJoin('a.participation', 'p')
                   ->leftJoin('p.phoneNumbers', 'pn')
                   ->where('a.participation = p.pid')
                   ->andWhere('p.event = :eid')
                   ->orderBy('a.nameFirst, a.nameLast', 'ASC');

        if ($filter !== null) {
            $qb->andWhere("a.aid IN(:participantList)")
               ->setParameter('participantList', $filter);
        }
        $qb->setParameter('eid', $eid);

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * Get the total amount of an events participants who are not deleted or whose participation is withdrawn
     *
     * @param Event $event
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function participantsCount(Event $event)
    {
        $eid = $event->getEid();
        /** @var \DateTime $start */
        $query
            = 'SELECT COUNT(*)
                 FROM participant a, participation p
                WHERE a.pid = p.pid
                  AND a.deleted_at IS NULL
                  AND (a.status & ' . ParticipantStatus::TYPE_STATUS_WITHDRAWN . ') != ' .
              ParticipantStatus::TYPE_STATUS_WITHDRAWN . '
                  AND p.eid = ?';

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($eid));

        return $stmt->fetchColumn();
    }

    /**
     * Get a list of ages of the participants who are not deleted or whose participation is withdrawn
     *
     * @param Event $event
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function participantsAgeList(Event $event)
    {
        $eid = $event->getEid();
        /** @var \DateTime $start */
        $start = $event->getStartDate();

        $query
            = 'SELECT a.birthday
                 FROM participant a, participation p
                WHERE a.pid = p.pid
                  AND a.deleted_at IS NULL
                  AND (a.status & ' . ParticipantStatus::TYPE_STATUS_WITHDRAWN . ') != ' .
              ParticipantStatus::TYPE_STATUS_WITHDRAWN . '
                  AND p.eid = ?';

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($eid));

        $birthdayList = $stmt->fetchAll();

        $ageList = array();
        foreach ($birthdayList as $participant) {
            $ageList[] = self::age(new \DateTime($participant['birthday']), $start);
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
        $ageList = $this->participantsAgeList($event);

        $ageDistribution = array();
        foreach ($ageList as $age) {
            $age = round($age);

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
        $eid = $event->getEid();
        $query
             = 'SELECT gender, COUNT(*) AS count
                 FROM participant a, participation p
                WHERE a.pid = p.pid
                  AND a.deleted_at IS NULL
                  AND (a.status & ' . ParticipantStatus::TYPE_STATUS_WITHDRAWN . ') != ' .
               ParticipantStatus::TYPE_STATUS_WITHDRAWN . '
                  AND p.eid = ?
             GROUP BY a.gender';

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($eid));

        $genderDistribution = array();
        foreach($stmt->fetchAll() as $distribution) {
            switch($distribution['gender']) {
                case Participant::TYPE_GENDER_FEMALE:
                    $genderDistribution[Participant::TYPE_GENDER_FEMALE] = array(
                        'type' => Participant::TYPE_GENDER_FEMALE,
                        'label' => Participant::LABEL_GENDER_FEMALE,
                        'count' => $distribution['count']
                    );
                    break;
                case Participant::TYPE_GENDER_MALE:
                    $genderDistribution[Participant::TYPE_GENDER_MALE] = array(
                        'type' => Participant::TYPE_GENDER_MALE,
                        'label' => Participant::LABEL_GENDER_MALE,
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
     * Calculate an age
     *
     * @param \DateTime  $birthday  The birthday of the person which age should be calculated
     * @param \DateTime  $deadline  The date where the calculation is desired
     * @param  bool|null $precision If you want the result to be rounded with round(), specify precision here
     * @return float                Age in years
     */
    public static function age(\DateTime $birthday, \DateTime $deadline, $precision = null)
    {
        $ageInDays  = $deadline->diff($birthday)
                               ->format('%a');
        $ageInYears = $ageInDays / self::DAYS_OF_YEAR;

        if ($precision) {
            return round($ageInYears, $precision);
        }
        return $ageInYears;
    }
}
