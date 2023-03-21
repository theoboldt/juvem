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
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
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
     * Get proposed
     *
     * @param ParticipantDetectingCustomFieldValue $customFieldValue
     * @param Event                                $event
     * @return array|Participant
     * @see Participant for custom field
     */
    public function proposedParticipants(
        ParticipantDetectingCustomFieldValue $customFieldValue,
        Attribute                            $customField,
        Event                                $event
    ): array {
        $this->calculateProposedParticipantsForEventAndAttributes($event, [$customField]);

        if (!$customFieldValue->getProposedParticipants()) {
            $this->calculateProposedParticipantsForEventAndAttributes($event, [$customField]);
        }

        $result = [];
        foreach ($customFieldValue->getProposedParticipants() as $aid) {
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
        if (!count($attributes)) {
            $this->logger->info(
                'No proposed participants fields for event {event}', ['event' => $event->getEid()]
            );
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
     * @param Event       $event        Related event
     * @param Attribute[] $customFields Attributes
     * @see Participant for transmitted @see Attribute
     */
    private function calculateProposedParticipantsForEventAndAttributes(Event $event, array $customFields)
    {
        $lockHandle = $this->lock($event);
        if ($lockHandle !== false && flock($lockHandle, LOCK_EX)) {
            $start = microtime(true);
            /** @var Participant[] $participants */
            $participants  = $this->repository->participantsList($event, null, true, true);
            $durationFetch = round((microtime(true) - $start) * 1000);

            /** @var Participant $participant */
            foreach ($participants as $participant) {
                $this->participantsCache[$participant->getAid()] = $participant;

                foreach ($customFields as $customField) {
                    $customFieldValueContainer = $participant->getCustomFieldValues()->getByCustomField($customField);
                    if ($customFieldValueContainer->getType() !== ParticipantDetectingCustomFieldValue::TYPE) {
                        continue;
                    }
                    /** @var ParticipantDetectingCustomFieldValue $customFieldValue */
                    $customFieldValue = $customFieldValueContainer->getValue();

                    $qualified = $this->calculateProposedParticipantsForCustomFieldValue(
                        $customFieldValueContainer, $participants
                    );

                    if ($customFieldValue->getParticipantAid() === null) {
                        //check for exact match
                        foreach ($qualified as $relatedParticipant) {
                            if ($relatedParticipant->getNameFirst() === $customFieldValue->getRelatedFirstName()
                                && $relatedParticipant->getNameLast() === $customFieldValue->getRelatedLastName()
                            ) {
                                $customFieldValue->setParticipantAid($relatedParticipant->getAid());
                                $customFieldValue->setParticipantFirstName($relatedParticipant->getNameFirst());
                                $customFieldValue->setParticipantLastName($relatedParticipant->getNameLast());
                                $customFieldValue->setIsSystemSelection(true);
                            }
                        }
                    }

                    $proposedIds = [];
                    /** @var Participant $qualifiedParticipant */
                    foreach ($qualified as $qualifiedParticipant) {
                        $proposedIds[] = $qualifiedParticipant->getAid();
                    }
                    $customFieldValue->setProposedParticipants($proposedIds);
                    $this->em->persist($participant);
                }
            }
            $startFlush = microtime(true);
            $this->em->flush();
            $durationFlush        = round((microtime(true) - $startFlush) * 1000);
            $durationParticipants = round((microtime(true) - $start) * 1000);
            $this->logger->info(
                'Calculated proposed participants for event {event} for {participants} participants at {attributes} attributes within {duration} ms ({durationFetch} ms of this spent to fetch them, {durationFlush} ms for flush)',
                [
                    'event'         => $event->getEid(),
                    'participants'  => count($participants),
                    'attributes'    => count($customFields),
                    'duration'      => $durationParticipants,
                    'durationFetch' => $durationFetch,
                    'durationFlush' => $durationFlush,
                ]
            );
            $this->release($event, $lockHandle);
        } else {
            $this->closeLockHandle($lockHandle);
            $this->logger->info(
                'Proposed participants calculation is locked for event {event}, retrying in few seconds',
                ['event' => $event->getEid()]
            );
            sleep(2);
            $this->calculateProposedParticipantsForEventAndAttributes($event, $customFields);
        }
    }


    /**
     * Calculate qualified similar
     *
     * @param CustomFieldValueContainer $customFieldValueContainer Custom field value container
     * @param array|Participant         $participants              Pool of possible matching @see Participant
     * @return array|Participant
     * @see Participant for transmitted custom field
     *
     */
    private function calculateProposedParticipantsForCustomFieldValue(
        CustomFieldValueContainer $customFieldValueContainer,
        array                     $participants
    ): array {
        if ($customFieldValueContainer->getType() !== ParticipantDetectingCustomFieldValue::TYPE) {
            return [];
        }
        /** @var ParticipantDetectingCustomFieldValue $customFieldValue */
        $customFieldValue = $customFieldValueContainer->getValue();
        $qualified        = [];

        /** @var Participant $participant */
        foreach ($participants as $participant) {
            $diffFirstName = levenshtein($customFieldValue->getRelatedFirstName(), trim($participant->getNameFirst()));
            $diffLastName  = levenshtein($customFieldValue->getRelatedLastName(), trim($participant->getNameLast()));

            if ($diffFirstName < 2 && $diffLastName < 2) {
                $qualified[($diffFirstName + $diffLastName)][] = $participant;
            } elseif ($diffFirstName < 3) {
                $qualified[($diffFirstName + 10)][] = $participant;
            } elseif ($diffLastName < 3) {
                $qualified[($diffLastName + 11)][] = $participant;
            }
        }

        ksort($qualified);
        $result = [];
        foreach ($qualified as $subQualified) {
            foreach ($subQualified as $participant) {
                $result[$participant->getAid()] = $participant;
            }
        }

        return array_values($result);
    }

    /**
     * Fetch @param int $aid Id
     *
     * @return Participant Entity
     * @see Participant by id from cache
     *
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
