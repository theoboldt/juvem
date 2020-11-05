<?php


namespace AppBundle\Entity;


class RelatedParticipant extends BaseRelatedHuman implements RelatedHumanInterface
{
    /**
     * Full participant data
     *
     * @var Participant
     */
    private $participant;
    
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
     * Get event
     *
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->participant->getEvent();
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
     * Get formatted participation date
     *
     * @return string
     */
    public function getCreatedDateFormatted(): string
    {
        return $this->participant->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME);
    }
    
}