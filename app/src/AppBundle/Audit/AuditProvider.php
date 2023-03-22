<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Audit;

use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Entity\CommentBase;
use AppBundle\Entity\EmployeeRepository;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipationRepository;
use AppBundle\Manager\CommentManager;
use Psr\Log\LoggerInterface;

class AuditProvider
{

    /**
     * @var CommentManager
     */
    private CommentManager $commentManager;

    /**
     * @var ParticipationRepository
     */
    private ParticipationRepository $participationRepository;

    /**
     * @var EmployeeRepository
     */
    private EmployeeRepository $employeeRepository;

    private LoggerInterface $logger;

    /**
     * @param CommentManager          $commentManager
     * @param ParticipationRepository $participationRepository
     * @param EmployeeRepository      $employeeRepository
     */
    public function __construct(
        CommentManager          $commentManager,
        ParticipationRepository $participationRepository,
        EmployeeRepository      $employeeRepository,
        LoggerInterface         $logger
    ) {
        $this->commentManager          = $commentManager;
        $this->participationRepository = $participationRepository;
        $this->employeeRepository      = $employeeRepository;
        $this->logger                  = $logger;
    }

    /**
     * Provide audit events
     *
     * @param Event              $event
     * @param \DateTimeInterface $date
     * @return AuditEvent[]
     */
    public function provideAuditEvents(Event $event, \DateTimeInterface $date): array
    {
        $auditEvents = [];

        $participants = $this->participationRepository->participantsList($event, null, true, true);
        $auditEvents  = array_merge(
            $auditEvents,
            $this->processItems($participants, $date)
        );

        $participations = [];
        /** @var Participant $participant */
        foreach ($participants as $participant) {
            $participations[$participant->getParticipation()->getId()] = $participant->getParticipation();
        }
        $auditEvents = array_merge(
            $auditEvents,
            $this->processItems($participations, $date)
        );

        $employees   = $this->employeeRepository->findForEvent($event, null, true);
        $auditEvents = array_merge(
            $auditEvents,
            $this->processItems($employees, $date)
        );


        $this->commentManager->ensureFetchedForEvent($event);
        foreach ($participants as $participant) {
            $comments    = $this->commentManager->forParticipant($participant);
            $auditEvents = array_merge(
                $auditEvents,
                $this->processItemComments($participant, $comments, $date)
            );
        }

        foreach ($participations as $participation) {
            $comments    = $this->commentManager->forParticipation($participation);
            $auditEvents = array_merge(
                $auditEvents,
                $this->processItemComments($participation, $comments, $date)
            );
        }

        foreach ($employees as $employee) {
            $comments    = $this->commentManager->forEmployee($employee);
            $auditEvents = array_merge(
                $auditEvents,
                $this->processItemComments($employee, $comments, $date)
            );
        }

        usort(
            $auditEvents,
            function (AuditEventInterface $a, AuditEventInterface $b) {
                return $b->getOccurrenceDate() <=> $a->getOccurrenceDate();
            }
        );

        return $auditEvents;
    }

    /**
     * Process item comments
     *
     * @param SupportsChangeTrackingInterface $item
     * @param array                           $comments
     * @param \DateTimeInterface              $date
     * @return AuditEventInterface[]
     */
    private function processItemComments(
        SupportsChangeTrackingInterface $item,
        array                           $comments,
        \DateTimeInterface              $date
    ) {
        $result = [];

        /** @var CommentBase $comment */
        foreach ($comments as $comment) {
            if ($comment instanceof ProvidesCreatedInterface) {
                if ($comment->getCreatedAt() >= $date) {
                    $result[] = AuditEvent::create($item, AuditEvent::TYPE_COMMENT_CREATE, $comment->getCreatedAt());
                }
            }
            if ($comment instanceof ProvidesModifiedInterface) {
                if ($comment->getModifiedAt() !== null
                    && $comment->getModifiedAt() >= $date
                    && ($comment instanceof ProvidesCreatedInterface &&
                        !$comment->getCreatedAt()->format('U') !== $comment->getModifiedAt()->format('U'))
                ) {
                    $result[] = AuditEvent::create($item, AuditEvent::TYPE_COMMENT_MODIFY, $comment->getModifiedAt());
                }
            }
            if ($comment instanceof SoftDeleteableInterface) {
                if ($comment->isDeleted() && $comment->getDeletedAt() >= $date) {
                    $result[] = AuditEvent::create($item, AuditEvent::TYPE_COMMENT_DELETE, $comment->getDeletedAt());
                }
            }
        }
        return $result;
    }

    /**
     * @param array              $items
     * @param \DateTimeInterface $date
     * @return AuditEventInterface[]
     */
    private function processItems(array $items, \DateTimeInterface $date): array
    {
        $result = [];

        foreach ($items as $item) {
            if (!$item instanceof SupportsChangeTrackingInterface) {
                $this->logger->warning(
                    'Audit Provider processed unexpected entity of class {class}',
                    ['class' => get_class($item)]
                );
                continue;
            }

            if ($item instanceof ProvidesCreatedInterface) {
                if ($item->getCreatedAt() >= $date) {
                    $result[] = AuditEvent::create($item, AuditEvent::TYPE_CREATE, $item->getCreatedAt());
                }
            }
            if ($item instanceof ProvidesModifiedInterface) {
                if ($item->getModifiedAt() !== null
                    && $item->getModifiedAt() >= $date
                    && ($item instanceof ProvidesCreatedInterface &&
                        !$item->getCreatedAt()->format('U') !== $item->getModifiedAt()->format('U'))
                ) {
                    $result[] = AuditEvent::create($item, AuditEvent::TYPE_MODIFY, $item->getModifiedAt());
                }
            }
            if ($item instanceof SoftDeleteableInterface) {
                if ($item->isDeleted() && $item->getDeletedAt() >= $date) {
                    $result[] = AuditEvent::create($item, AuditEvent::TYPE_DELETE, $item->getDeletedAt());
                }
            }
        }

        return $result;
    }

}
