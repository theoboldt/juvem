<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Calendar\CalDav;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class CalDavEvent
{
    private string $href;

    private string $etag;

    private VCalendar $calendarData;

    private string $status;

    /**
     * @param string    $href
     * @param string    $etag
     * @param VCalendar $calendarData
     * @param string    $status
     */
    public function __construct(string $href, string $etag, VCalendar $calendarData, string $status)
    {
        $this->href         = $href;
        $this->etag         = $etag;
        $this->calendarData = $calendarData;
        $this->status       = $status;
    }

    /**
     * @return string
     */
    public function getHref(): string
    {
        return $this->href;
    }

    /**
     * @return string
     */
    public function getEtag(): string
    {
        return $this->etag;
    }

    /**
     * @return VCalendar
     */
    public function getCalendarData(): VCalendar
    {
        return $this->calendarData;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get VEvent component from object
     *
     * @return VEvent
     */
    private function getVevent(): VEvent
    {
        $component = $this->calendarData->getBaseComponent('VEVENT');
        if ($component instanceof VEvent) {
            return $component;
        } elseif ($component === null) {
            throw new CalDavOperationFailedException('Event object does not have a VEVENT component');
        } else {
            throw new CalDavOperationFailedException('Unknown class ' . get_class($component) . ' provided');
        }
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStartDate(): \DateTimeImmutable
    {
        $vevent = $this->getVevent();
        return $vevent->DTSTART->getDateTime();
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEndDate(): \DateTimeImmutable
    {
        $vevent = $this->getVevent();
        return $vevent->DTEND->getDateTime();
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        $vevent = $this->getVevent();
        return (string)$vevent->UID;
    }

    /**
     * @return string
     */
    public function getSummary(): string
    {
        $vevent = $this->getVevent();
        return (string)$vevent->SUMMARY;
    }
}
