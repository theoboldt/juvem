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

        usort(
            $auditEvents,
            function (AuditEventInterface $a, AuditEventInterface $b) {
                return $b->getOccurrenceDate() <=> $a->getOccurrenceDate();
            }
        );
        
        return $auditEvents;
    }

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
