<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Controller\Event;


use AppBundle\Entity\Event;
use Doctrine\Common\Persistence\ManagerRegistry;

trait WaitingListFlashTrait
{
    /**
     * Fetch participants count and add to cache
     *
     * @param Event $event
     */
    protected function addParticipantsCountToCache(Event $event)
    {
        $repository        = $this->getDoctrine()->getRepository(Event::class);
        $participantsCount = $repository->participantsCount($event);
        $event->setParticipantsCounts($participantsCount);
    }

    /**
     * Register flash messages regarding waiting list if required
     *
     * @param Event $event
     */
    protected function addWaitingListFlashIfRequired(Event $event)
    {
        if ($event->hasWaitingListThreshold() && $event->isActive()) {
            $this->addParticipantsCountToCache($event);
            if ($event->getParticipantsCount() >= $event->getWaitingListThreshold()) {
                $this->addFlash(
                    'warning',
                    'Im Moment scheinen alle Plätze der Veranstaltung belegt zu sein. Neue Anmeldungen erfolgen zunächst auf <b>Warteliste</b>.'
                );
            } elseif ($event->getParticipantsCount() + 3 >= $event->getWaitingListThreshold()) {
                $this->addFlash(
                    'warning',
                    'Im Moment scheinen bei dieser Veranstaltung nur noch <b>wenig Plätze frei</b> zu sein. Es kann sein, das neue Anmeldungen auf Warteliste erfolgen müssen.'
                );
            }
        }
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return ManagerRegistry
     *
     * @throws \LogicException If DoctrineBundle is not available
     *
     * @final since version 3.4
     */
    abstract protected function getDoctrine();

    /**
     * Adds a flash message to the current session for type.
     *
     * @param string $type    The type
     * @param string $message The message
     *
     * @throws \LogicException
     */
    abstract protected function addFlash($type, $message);

}