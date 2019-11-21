<?php


namespace AppBundle\Entity;


class RelatedParticipant
{
    /**
     * Full participant data
     *
     * @var Participant
     */
    private $participant;
    
    /**
     * Usually event title, might contain year or even date if event title is ambiguous
     *
     * @var string|null
     */
    private $title;
    
    /**
     * RelatedParticipant constructor.
     *
     * @param Participant $participant
     */
    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
    }
    
    /**
     * Get event id
     *
     * @return int
     */
    public function getEid(): int
    {
        return $this->participant->getEvent()->getEid();
    }
    
    /**
     * Get pid
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->participant->getParticipation()->getPid();
    }
    
    /**
     * Get aid
     *
     * @return int
     */
    public function getAid(): int
    {
        return $this->participant->getAid();
    }
    
    /**
     * @return Participant
     */
    public function getParticipant(): Participant
    {
        return $this->participant;
    }
    
    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->participant->getEvent();
    }
    
    /**
     * @return string
     */
    public function getTitle(): string
    {
        if ($this->title === null) {
            return $this->participant->getEvent()->getTitle();
        }
        return $this->title;
    }
    
    /**
     * @param string|null $title
     * @return RelatedParticipant
     */
    public function setTitle(?string $title): RelatedParticipant
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Determine if event is deleted
     *
     * @return bool
     */
    public function isEventDeleted(): bool
    {
        return $this->participant->getParticipation()->getEvent()->getDeletedAt() !== null;
    }
    
    /**
     * Determine if participant is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->participant->getDeletedAt() !== null;
    }
    
    /**
     * Determine if participant is rejected or withdrawn
     *
     * @return bool
     */
    public function isWithdrawnOrRejected(): bool
    {
        return $this->participant->isRejected() || $this->participant->isWithdrawn();
    }
    
    /**
     * Determine if participant is confirmed
     *
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->participant->isConfirmed();
    }
    
    /**
     * Get formatted event start date
     *
     * @return string
     */
    public function getEventDateFormatted(): string
    {
        return $this->participant->getParticipation()->getEvent()->getStartDate()->format(Event::DATE_FORMAT_DATE);
    }
    
    /**
     * Get formatted participation date
     *
     * @return string
     */
    public function getParticipationDateFormatted(): string
    {
        return $this->participant->getParticipation()->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME);
    }
    
}