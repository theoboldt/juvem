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


use AppBundle\Entity\Event;

abstract class RelatedParticipantsLocker
{

    /**
     * Path to temporary dir
     *
     * @var string
     */
    protected $tmpPath;


    /**
     * RelatedParticipantsFinder constructor.
     *
     * @param string $tmpPath
     */
    public function __construct(string $tmpPath)
    {
        $this->tmpPath = rtrim($tmpPath, '/');
    }

    /**
     * Create lock and returns handle
     *
     * @param Event $event Event to lock
     * @return bool|resource
     */
    protected function lock(Event $event)
    {
        touch($this->lockPath($event));
        return fopen($this->lockPath($event), 'r+');
    }

    /**
     * Release lock and delete file
     *
     * @param Event     $event  Related event
     * @param  resource $handle Related handle
     */
    protected function release(Event $event, $handle)
    {
        flock($handle, LOCK_UN);
        fclose($handle);
        unlink($this->lockPath($event));
    }

    /**
     * Get lock file path for transmitted @see Event
     *
     * @param Event $event
     * @return string
     */
    protected function lockPath(Event $event)
    {
        return $this->tmpPath . '/_related_participants_finder_' . $event->getEid() . '.lock';
    }
}
