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
use AppBundle\Form\ParticipantDetectingType;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

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
     * Logger
     * 
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Cache for @see Participant entities identified by their aid
     *
     * @var array|Participant[]
     */
    private $participantsCache = [];

    /**
     * RelatedParticipantsFinder constructor.
     *
     * @param string          $tmpPath
     * @param EntityManager   $em
     * @param LoggerInterface $logger
     */
    public function __construct(string $tmpPath, EntityManager $em, LoggerInterface $logger)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(Participation::class);
        $this->logger     = $logger;
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
            $this->calculateProposedParticipantsForEventAndAttributes($fillout->getEvent(), [$fillout->getAttribute()]);

        if (!$filloutValue->hasProposedParticipantsCalculated()) {
            $this->calculateProposedParticipantsForEventAndAttributes($fillout->getEvent(), [$fillout->getAttribute()]);
        }
        $result = [];
        $filloutValue = $fillout->getValue(); //refetch
        foreach ($filloutValue->getProposedParticipantIds() as $aid) {
            $result[] = $this->getParticipant($aid);
        }
        return $result;
    }

    /**
     * Calculate for all fields of event
     * 
     * @param Event $event
     */
    public function calculateProposedParticipantsForEvent(Event $event): void
    {
        $attributes = [];
        /** @var Attribute $attribute */
        foreach ($event->getAcquisitionAttributes(true, true, true, true, true) as $attribute) {
            if ($attribute->getFieldType(false) === ParticipantDetectingType::class) {
                $attributes[] = $attribute;
            }
        }
        if (count($attributes)) {
            return;
        }
        $this->logger->info(
            'Going to calculate proposed participants for event {event}', ['event' => $event->getEid()]
        );
        $this->calculateProposedParticipantsForEventAndAttributes($event, $attributes);
    }

    /**
     * Calculate all
     *
     * @param Event       $event      Related event
     * @param Attribute[] $attributes Attributes
     * @see Participant for transmitted @see Attribute
     */
    private function calculateProposedParticipantsForEventAndAttributes(Event $event, array $attributes)
    {
        $lockHandle = $this->lock($event);
        if ($lockHandle !== false && flock($lockHandle, LOCK_EX)) {
            /** @var Participant[] $participants */
            $participants = $this->repository->participantsList($event, null, true, true);

            /** @var Participant $participant */
            foreach ($participants as $participant) {
                $this->participantsCache[$participant->getAid()] = $participant;

                foreach ($attributes as $attribute) {
                    $time = microtime(true);
                    try {
                        $fillout = $participant->getAcquisitionAttributeFillout($attribute->getBid(), false);
                    } catch (RequestedFilloutNotFoundException $e) {
                        continue;
                    }
                    $qualified = $this->calculateProposedParticipantsForFillout($fillout, $participants);

                    /** @var ParticipantFilloutValue $value */
                    $filloutRawValue = $fillout->getRawValue();
                    if (is_string($filloutRawValue)) {
                        $filloutRawValue = json_decode($filloutRawValue, true);
                    }
                    if (!is_array($filloutRawValue)) {
                        $filloutRawValue = [];
                    }

                    /** @var ParticipantFilloutValue $value */
                    $value = $fillout->getValue();
                    if ($value->getSelectedParticipantId() === null) {
                        //check for exact match
                        foreach ($qualified as $relatedParticipant) {
                            if ($relatedParticipant->getNameFirst() === $value->getRelatedFirstName()
                                && $relatedParticipant->getNameLast() === $value->getRelatedLastName()
                            ) {
                                $filloutRawValue[ParticipantFilloutValue::KEY_SELECTED_AID]
                                    = $relatedParticipant->getAid();
                                $filloutRawValue[ParticipantFilloutValue::KEY_SELECTED_FIRST]
                                    = $relatedParticipant->getNameFirst();
                                $filloutRawValue[ParticipantFilloutValue::KEY_SELECTED_LAST]
                                    = $relatedParticipant->getNameLast();
                                $filloutRawValue[ParticipantFilloutValue::KEY_SYSTEM_SELECTION] = true;
                            }
                        }
                    }

                    $filloutRawValue[ParticipantFilloutValue::KEY_PROPOSED_IDS] = [];
                    /** @var Participant $qualifiedParticipant */
                    foreach ($qualified as $qualifiedParticipant) {
                        $filloutRawValue[ParticipantFilloutValue::KEY_PROPOSED_IDS][] = $qualifiedParticipant->getAid();
                    }
                    $fillout->setValue($filloutRawValue);
                    $this->em->persist($fillout);

                    $duration = round((microtime(true) - $time) * 1000);
                    $this->logger->info(
                        'Calculated proposed participants for event {event} and attribute done within {duration} ms',
                        ['event' => $event->getEid(), 'attribute' => $attribute->getId(), 'duration' => $duration]
                    );
                }
            }
            $this->em->flush();
            $this->release($event, $lockHandle);
        } else {
            $this->closeLockHandle($lockHandle);
            $this->logger->info(
                'Proposed participants calculation is locked for event {event}, retrying in few seconds',
                ['event' => $event->getEid()]
            );
            sleep(2);
            $this->calculateProposedParticipantsForEventAndAttributes($event, $attributes);
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
                        $this->em->getConnection()->executeStatement(
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
            } elseif ($diffFirstName < 3) {
                $qualified[($diffFirstName + 10)][] = $participant;
            } elseif ($diffLastName < 3) {
                $qualified[($diffLastName + 11)][] = $participant;
            }
        }

        ksort($qualified);
        $result    = [];
        foreach ($qualified as $subQualified) {
            foreach ($subQualified as $participant) {
                $result[$participant->getAid()] = $participant;
            }
        }

        return array_values($result);
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
