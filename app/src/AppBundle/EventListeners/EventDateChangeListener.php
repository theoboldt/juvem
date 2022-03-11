<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventListeners;

use AppBundle\Entity\Event;
use AppBundle\Manager\Calendar\CalendarManager;
use AppBundle\Manager\Calendar\CalendarOperationFailedException;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Log\LoggerInterface;

class EventDateChangeListener
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CalendarManager
     */
    private CalendarManager $calendarManager;

    /**
     * @param LoggerInterface $logger
     * @param CalendarManager $calendarManager
     */
    public function __construct(LoggerInterface $logger, CalendarManager $calendarManager)
    {
        $this->logger          = $logger;
        $this->calendarManager = $calendarManager;
    }


    /**
     * Update entity
     *
     * @param PreUpdateEventArgs $args Pre update args
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getEntity();
        if ($entity instanceof Event) {
            $this->updateEventCalendarEntry($entity);
        }
    }

    /**
     * On entity flush check for entity inserts & deletes
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Event) {
                $this->updateEventCalendarEntry($entity);
            }
        }
    }
    
    /**
     * Update event calendar entry
     *
     * @param Event $event
     * @return void
     */
    private function updateEventCalendarEntry(Event $event): void
    {
        if (!$event->isDeleted()) {
            $this->calendarManager->updateEventCalendarEntry($event);
        } else {
            try {
                $this->calendarManager->removeCalendarObject($event);
            } catch (CalendarOperationFailedException $e) {
                //intentionaly left empty
            }
        }
    }

}
