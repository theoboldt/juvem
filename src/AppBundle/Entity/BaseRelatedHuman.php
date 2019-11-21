<?php

namespace AppBundle\Entity;


abstract class BaseRelatedHuman
{
    /**
     * Usually event title, might contain year or even date if event title is ambiguous
     *
     * @var string|null
     */
    protected $title;
    
    
    /**
     * Get event
     *
     * @return Event
     */
    abstract public function getEvent(): Event;
    
    /**
     * Get event id
     *
     * @return int
     */
    public function getEid(): int
    {
        return $this->getEvent()->getEid();
    }
    
    /**
     * @return string
     */
    public function getTitle(): string
    {
        if ($this->title === null) {
            return $this->getEvent()->getTitle();
        }
        return $this->title;
    }
    
    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
    
    /**
     * Determine if event is deleted
     *
     * @return bool
     */
    public function isEventDeleted(): bool
    {
        return $this->getEvent()->getDeletedAt() !== null;
    }
    
    /**
     * Get formatted event start date
     *
     * @return string
     */
    public function getEventDateFormatted(): string
    {
        return $this->getEvent()->getStartDate()->format(Event::DATE_FORMAT_DATE);
    }
    
    
}