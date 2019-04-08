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

/**
 * Class ParticipationRepository
 *
 * @package AppBundle\Entity
 */
class ParticipationRepository extends EntityRepository
{

    /**
     * Find one participation with all related participants, the related event and acquisition attributes
     *
     * @param int $pid Id of related participation
     * @return Participation|null
     */
    public function findDetailed(int $pid)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p', 'e', 's', 'a', 'n', 'c')
           ->innerJoin('p.event', 'e')
           ->leftJoin('e.acquisitionAttributes', 's')
           ->leftJoin('s.choiceOptions', 'c')
           ->leftJoin('p.participants', 'a')
           ->leftJoin('p.phoneNumbers', 'n')
           ->addOrderBy('p.nameLast')
           ->addOrderBy('p.nameFirst')
           ->andWhere($qb->expr()->eq('p.pid', ':pid'))
           ->setParameter('pid', $pid);
        $result = $qb->getQuery()->execute();
        if (count($result)) {
            $result = reset($result);
            return $result;
        }
        return null;
    }

    /**
     * Get a list of participations of an event
     *
     * @param   Event      $event                    The event
     * @param   bool       $includeDeleted           Set to true to include deleted participations
     * @param   bool       $includeWithdrawnRejected Set to true to include participations who have only
     *                                               withdrawn participants assigned
     * @param   null|array $filter                   Transmit a list of aids to filter out participants
     *                                               not included in list
     * @return  array|Participation[]
     */
    public function participationsList(
        Event $event,
        $includeDeleted = false,
        $includeWithdrawnRejected = false,
        array $filter = null
    ) {
        $eid = $event->getEid();

        $qb = $this->createQueryBuilder('p')
                   ->andWhere('p.event = :eid')
                   ->orderBy('p.nameFirst, p.nameLast', 'ASC');

        if (!$includeDeleted) {
            $qb->andWhere('p.deletedAt IS NULL');
        }

        if (!$includeWithdrawnRejected) {
            $qb->andWhere(
                sprintf(
                    'EXISTS (SELECT a
                                   FROM AppBundle:Participant a
                                  WHERE p.pid = a.participation
                                    AND (BIT_AND(a.status, %1$d) != %1$d) AND BIT_AND(a.status, %2$d) != %2$d)',
                    ParticipantStatus::TYPE_STATUS_WITHDRAWN,
                    ParticipantStatus::TYPE_STATUS_REJECTED
                )
            );
        }

        $qb->setParameter('eid', $eid);

        if ($filter !== null) {
            $qb->andWhere("p.pid IN(:participationList)")
               ->setParameter('participationList', $filter);
        }

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * Get a list of participants of an event
     *
     * @param Event      $event                      The event
     * @param null|array $filter                     Transmit a list of aids to filter out participants
     *                                               not included in list
     * @param bool       $includeDeleted             Set to true to include deleted participants
     * @param bool       $includeWithdrawnRejected   Set to true to include withdrawn participants
     * @return  array
     */
    public function participantsList(
        Event $event,
        array $filter = null,
        $includeDeleted = false,
        $includeWithdrawnRejected = false
    ) {
        $eid = $event->getEid();

        if ($filter === []) {
            return []; //empty result
        }

        //re-fetch event in order to ensure attributes and options were fetched
        $qb = $this->_em->createQueryBuilder();
        $qb->select('e', 'a', 'o')
           ->from(Event::class, 'e')
           ->leftJoin('e.acquisitionAttributes', 'a')
           ->leftJoin('a.choiceOptions', 'o')
           ->andWhere($qb->expr()->eq('e.eid', ':eid'))
           ->setParameter('eid', $eid);
        $event = $qb->getQuery()->getSingleResult();

        //fetch @see Participant
        $qb = $this->_em->createQueryBuilder();
        $qb->select('a', 'aaf')
           ->from(Participant::class, 'a', 'a.aid')
           ->innerJoin('a.participation', 'p')
           ->leftJoin('a.acquisitionAttributeFillouts', 'aaf')
           ->andWhere('p.event = :eid')
           ->orderBy('a.nameLast, a.nameFirst', 'ASC');

        if (!$includeWithdrawnRejected) {
            $qb->andWhere(
                sprintf(
                    '(BIT_AND(a.status, %1$d) != %1$d AND BIT_AND(a.status, %2$d) != %2$d)',
                    ParticipantStatus::TYPE_STATUS_WITHDRAWN,
                    ParticipantStatus::TYPE_STATUS_REJECTED
                )
            );
        }
        if (!$includeDeleted) {
            $qb->andWhere('a.deletedAt IS NULL');
        }
        if ($filter !== null) {
            $qb->andWhere("a.aid IN(:participantList)")
               ->setParameter('participantList', $filter);
        }
        $qb->setParameter('eid', $eid);

        $result = $qb->getQuery()->execute();

        //fetch @see Participation and phone numbers
        $qb = $this->_em->createQueryBuilder();
        $qb->select('p, pn, paf')
           ->from(Participation::class, 'p')
           ->leftJoin('p.acquisitionAttributeFillouts', 'paf')
           ->leftJoin('p.phoneNumbers', 'pn')
           ->andWhere('p.event = :eid')
           ->setParameter('eid', $eid);
        if (!$includeDeleted) {
            $qb->andWhere('p.deletedAt IS NULL');
        }

        $participations = $qb->getQuery()->execute();

        return $result;
    }

    /**
     * Find related participants by comparing birthday (exact) and name (fuzzy)
     *
     * @param Participant $baseParticipant Participant for compare
     * @return array|Participant[]         Related participants
     */
    public function relatedParticipants(Participant $baseParticipant)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('a, p, e')
           ->from(Participant::class, 'a')
           ->innerJoin('a.participation', 'p')
           ->innerJoin('p.event', 'e')
           ->andWhere($qb->expr()->eq('a.birthday', ':birthday'))
           ->orderBy('e.startDate', 'DESC')
           ->setParameter('birthday', $baseParticipant->getBirthday()->format('Y-m-d'));
        $query = $qb->getQuery();

        $aid       = $baseParticipant->getAid();
        $firstName = trim($baseParticipant->getNameFirst());
        $lastName  = trim($baseParticipant->getNameLast());

        $result = [];

        /** @var Participant $participant */
        foreach ($query->execute() as $participant) {
            if ($aid != $participant->getAid()
                && levenshtein($firstName, trim($participant->getNameFirst())) < 5
                && levenshtein($lastName, trim($participant->getNameLast())) < 5
            ) {
                $result[] = $participant;
            }
        }

        return $result;
    }
}
