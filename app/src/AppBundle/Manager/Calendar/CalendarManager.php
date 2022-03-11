<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Calendar;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Manager\Calendar\CalDav\CalDavConnector;
use AppBundle\Manager\Calendar\CalDav\CalDavVCalendarObjectFactory;
use Psr\Log\LoggerInterface;

class CalendarManager
{

    /**
     * @var CalDavConnector
     */
    private ?CalDavConnector $connector;

    /**
     * @var CalDavVCalendarObjectFactory
     */
    private CalDavVCalendarObjectFactory $calDavObjectFactory;

    /**
     * @var EventRepository
     */
    private EventRepository $eventRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CalDavConnector|null         $connector
     * @param CalDavVCalendarObjectFactory $calDavCreator
     * @param EventRepository              $eventRepository
     * @param LoggerInterface              $logger
     */
    public function __construct(
        ?CalDavConnector             $connector,
        CalDavVCalendarObjectFactory $calDavCreator,
        EventRepository              $eventRepository,
        LoggerInterface              $logger
    ) {
        $this->connector           = $connector;
        $this->eventRepository     = $eventRepository;
        $this->calDavObjectFactory = $calDavCreator;
        $this->logger              = $logger;
    }

    /**
     * Synchronize calendar
     *
     * @return void
     */
    public function sync(): void
    {
        if (!$this->connector) {
            return;
        }
        $start = microtime(true);

        $calendarEvents = $this->connector->fetchCalendarObjects();
        $juvemEvents    = $this->eventRepository->findAll();
        foreach ($juvemEvents as $juvemEvent) {
            if (!$juvemEvent->isDeleted() && $juvemEvent->isCalendarEntryEnabled()) {
                $this->updateEventCalendarEntry($juvemEvent);
            } else {
                $name = self::createJuvemEventName($juvemEvent);
                foreach ($calendarEvents as $calendarEvent) {
                    if (strpos($calendarEvent->getHref(), $name . '.ics') !== false) {
                        $this->connector->removeCalendarObject($name);
                    }
                }
            }
        }

        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->notice(
            'Synchronized calendar within {duration} ms', ['duration' => $duration]
        );
    }

    /**
     * Delete calendar object
     *
     * @param string $name
     * @return void
     */
    public function removeCalendarObject(Event $juvemEvent)
    {
        $name = self::createJuvemEventName($juvemEvent);
        $this->connector->removeCalendarObject($name);
    }

    /**
     * Create or update a calendar entry for transmitted event
     *
     * @param Event $juvemEvent
     * @return void
     */
    public function updateEventCalendarEntry(Event $juvemEvent)
    {
        $calendar = $this->calDavObjectFactory->createForJuvemEvent($juvemEvent);

        $this->connector->updateCalendarObject(
            self::createJuvemEventName($juvemEvent),
            $calendar
        );
    }

    /**
     * Create calendar event name for juvem event
     *
     * @param Event $juvemEvent
     * @return string
     */
    public static function createJuvemEventName(Event $juvemEvent): string
    {
        return 'juvem-event-' . $juvemEvent->getEid();
    }
}
