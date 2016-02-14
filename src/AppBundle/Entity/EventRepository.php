<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

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
     * Get the total amount of an events participants
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
                  AND p.eid = ?';

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($eid));

        return $stmt->fetchColumn();
    }

    /**
     * Get a list of ages of the participants
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
                  AND p.eid = ?';

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($eid));

        $birthdayList = $stmt->fetchAll();

        $ageList = array();
        foreach ($birthdayList as $participant) {
            $birthday  = new \DateTime($participant['birthday']);
            $ageInDays = $start->diff($birthday)
                               ->format('%a');
            $ageList[] = $ageInDays / self::DAYS_OF_YEAR;
        }

        return $ageList;
    }

    /**
     * Get the age distribution of an event
     *
     * @param Event     $event
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
}
