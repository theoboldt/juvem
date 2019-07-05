<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\ParticipantFilloutValue;
use AppBundle\Entity\AcquisitionAttribute\RequestedFilloutNotFoundException;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationRepository;
use Doctrine\ORM\EntityManager;

class RelatedParticipantsFinder extends RelatedParticipantsLocker
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Repository for @see Participant
     *
     * @var ParticipationRepository
     */
    private $repository;

    /**
     * Cache for @see Participant entities identified by their aid
     *
     * @var array|Participant[]
     */
    private $participantsCache = [];

    /**
     * RelatedParticipantsFinder constructor.
     *
     * @param string        $tmpPath
     * @param EntityManager $em
     */
    public function __construct(string $tmpPath, EntityManager $em)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(Participation::class);
        parent::__construct($tmpPath);
    }

    /**
     * Get proposed @see Participant for transmitted @see Fillout
     *
     * @param Fillout $fillout Related fillout
     * @return array|Participant[]
     */
    public function proposedParticipants(Fillout $fillout): array
    {
        $filloutValue = $fillout->getValue();
        if (!$filloutValue instanceof ParticipantFilloutValue) {
            return [];
        }
        if (!$filloutValue->hasProposedParticipantsCalculated()) {
            $this->calculateProposedParticipantsForEvent($fillout->getEvent(), $fillout->getAttribute());
        }
        $result = [];
        $filloutValue = $fillout->getValue(); //refetch
        foreach ($filloutValue->getProposedParticipantIds() as $aid) {
            $result[] = $this->getParticipant($aid);
        }
        return $result;
    }

    /**
     * Calculate all @see Participant for transmitted @see Attribute
     *
     * @param Event     $event     Related event
     * @param Attribute $attribute Attribute
     */
    private function calculateProposedParticipantsForEvent(Event $event, Attribute $attribute)
    {

        $lockHandle = $this->lock($event);
        if ($lockHandle !== false && flock($lockHandle, LOCK_EX)) {
            /** @var Participant[] $participants */
            $participants = $this->repository->participantsList($event, null, true, true);

            /** @var Participant $participant */
            foreach ($participants as $participant) {
                $this->participantsCache[$participant->getAid()] = $participant;

                try {
                    $fillout = $participant->getAcquisitionAttributeFillout($attribute->getBid(), false);
                } catch (RequestedFilloutNotFoundException $e) {
                    continue;
                }
                $qualified = $this->calculateProposedParticipantsForFillout($fillout, $participants);

                /** @var ParticipantFilloutValue $value */
                $value           = $fillout->getValue();
                $filloutRawValue = $value->getFormValue();

                $filloutRawValue['proposed_aids'] = [];
                /** @var Participant $qualifiedParticipant */
                foreach ($qualified as $qualifiedParticipant) {
                    $filloutRawValue['proposed_aids'][] = $qualifiedParticipant->getAid();
                }
                $fillout->setValue($filloutRawValue);
                $this->em->persist($fillout);
            }
            $this->em->flush();
            $this->release($event, $lockHandle);
        } else {
            $this->closeLockHandle($lockHandle);
            sleep(2);
            $this->calculateProposedParticipantsForEvent($event, $attribute);
        }
    }

    /**
     * Reset proposed participants for an complete event
     *
     * @param Event $event
     */
    public function resetProposedParticipantsForEvent(Event $event)
    {
        $lockHandle = $this->lock($event);
        if ($lockHandle !== false && flock($lockHandle, LOCK_EX)) {
            $result = $this->em->getConnection()->executeQuery(
                'SELECT acquisition_attribute_fillout.oid, acquisition_attribute_fillout.value AS fillout_value
                   FROM acquisition_attribute_fillout
             INNER JOIN acquisition_attribute ON (acquisition_attribute_fillout.bid = acquisition_attribute.bid)
             INNER JOIN event_acquisition_attribute ON (event_acquisition_attribute.bid = acquisition_attribute.bid)
                  WHERE acquisition_attribute.field_type = ?
                    AND event_acquisition_attribute.eid = ?',
                [ParticipantDetectingType::class, $event->getEid()]
            );
            $this->em->getConnection()->beginTransaction();
            while ($row = $result->fetch()) {
                $filloutValue = $row['fillout_value'];
                if ($filloutValue !== null) {
                    $filloutDecoded = json_decode($filloutValue, true);
                    if (is_array($filloutDecoded) &&
                        isset($filloutDecoded[ParticipantFilloutValue::KEY_PROPOSED_IDS])) {
                        $filloutDecoded[ParticipantFilloutValue::KEY_PROPOSED_IDS] = [];
                        $this->em->getConnection()->executeUpdate(
                            'UPDATE acquisition_attribute_fillout SET value = ? WHERE oid = ?',
                            [json_encode($filloutDecoded), $row['oid']]
                        );
                    }
                }
            }
            $this->em->getConnection()->commit();
        } else {
            $this->closeLockHandle($lockHandle);
            sleep(2);
            $this->resetProposedParticipantsForEvent($event);
        }
    }

    /**
     * Calculate qualified similar @see Participant for transmitted @see Fillout
     *
     * @param Fillout             $fillout      Source @see Fillout
     * @param array|Participant[] $participants Pool of possible matching @see Participant
     * @return array|Participant[]
     */
    private function calculateProposedParticipantsForFillout(Fillout $fillout, array $participants): array
    {
        $filloutValue = $fillout->getValue();
        if (!$filloutValue instanceof ParticipantFilloutValue) {
            return [];
        }
        $qualified = [];

        /** @var Participant $participant */
        foreach ($participants as $participant) {
            $diffFirstName = levenshtein($filloutValue->getRelatedFirstName(), trim($participant->getNameFirst()));
            $diffLastName  = levenshtein($filloutValue->getRelatedLastName(), trim($participant->getNameLast()));

            if ($diffFirstName < 2 && $diffLastName < 2) {
                $qualified[($diffFirstName + $diffLastName)][] = $participant;
            }
        }

        ksort($qualified);
        $qualified = array_reverse($qualified);
        $result    = [];
        foreach ($qualified as $subQualified) {
            foreach ($subQualified as $participant) {
                $result[] = $participant;
            }
        }

        return $result;
    }

    /**
     * Fetch @see Participant by id from cache
     *
     * @param int $aid Id
     * @return Participant Entity
     */
    private function getParticipant(int $aid): Participant
    {
        if (!isset($this->participantsCache[$aid])) {
            $qb = $this->em->createQueryBuilder();
            $qb->select('p', 'e', 'a')
               ->from(Participant::class, 'a')
               ->innerJoin('a.participation', 'p')
               ->innerJoin('p.event', 'e')
               ->andWhere($qb->expr()->eq('a.aid', ':aid'))
               ->setParameter('aid', $aid);
            $result = $qb->getQuery()->execute();
            if (count($result)) {
                $this->participantsCache[$aid] = reset($result);
            } else {
                throw new \RuntimeException('Participant not found');
            }
        }

        return $this->participantsCache[$aid];
    }

}
