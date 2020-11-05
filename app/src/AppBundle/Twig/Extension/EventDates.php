<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Twig\Extension;

use AppBundle\Entity\Event;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for easy display and formatting of event start/end dates/times
 *
 * Class EventDates
 *
 * @package AppBundle\Twig\Extension
 */
class EventDates extends AbstractExtension
{

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'eventStartDate',
                [$this, 'eventStartDate'],
                ['pre_escape' => 'html', 'is_safe' => ['html']]
            ),
            new TwigFilter(
                'eventEndDate',
                [$this, 'eventEndDate'],
                ['pre_escape' => 'html', 'is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Format event start data and apply itemprop markup
     *
     * @param Event $event Event to parse
     * @return string   Html formatted date
     */
    public function eventStartDate(Event $event) {
        $label = $event->getStartDate()->format(Event::DATE_FORMAT_DATE);
        $code  = $event->getStartDate()->format('Y-m-d');

        if ($event->hasStartTime()) {
            $label  .= ' '.$event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            $code   .= 'T'.$event->getStartTime()->format('H:i');
        }

        return sprintf('<span itemprop="startDate" content="%s">%s</span>', $code, $label);
    }

    /**
     * Format event end data and apply itemprop markup
     *
     * @param Event $event Event to parse
     * @return string   Html formatted date
     */
    public function eventEndDate(Event $event) {
        $label = '';
        $code  = '';

        if ($event->hasEndDate()) {
            $label  .= $event->getEndDate()->format(Event::DATE_FORMAT_DATE);
            $code   .= $event->getEndDate()->format('Y-m-d');
        } else {
            $code   .= $event->getStartDate()->format('Y-m-d');
        }
        if ($event->hasEndTime()) {
            $label  .= ' '.$event->getEndTime()->format(Event::DATE_FORMAT_TIME);
            $code   .= 'T'.$event->getEndTime()->format('H:i');
        }

        return sprintf('<span itemprop="endDate" content="%s">%s</span>', $code, $label);
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'eventdates';
    }
}
