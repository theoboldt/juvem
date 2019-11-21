<?php

namespace AppBundle\Entity;


interface RelatedHumanInterface
{
    
    /**
     * Get event id
     *
     * @return int
     */
    public function getEid(): int;
    
    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent(): Event;
    
    
    /**
     * @return string
     */
    public function getTitle(): string;
    
    
    /**
     * @param string|null $title
     * @return void
     */
    public function setTitle(?string $title): void;
    
    /**
     * Determine if event is deleted
     *
     * @return bool
     */
    public function isEventDeleted(): bool;
    
    /**
     * Determine if human is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool;
    
    
    /**
     * Get formatted event start date
     *
     * @return string
     */
    public function getEventDateFormatted(): string;
    
    /**
     * Get formatted participation date
     *
     * @return string
     */
    public function getCreatedDateFormatted(): string;
    
}