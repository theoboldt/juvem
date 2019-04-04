<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Form;


use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Participant;
use DateTime;

class GroupFieldAssignEntityChoiceOption
{
    /**
     * Related event
     *
     * @var Event
     */
    private $event;
    
    /**
     * Entity id
     *
     * @var int
     */
    private $id;
    
    /**
     * First name
     *
     * @var string
     */
    private $nameFirst;
    
    /**
     * Last name
     *
     * @var string
     */
    private $nameLast;
    
    /**
     * Groups this entity is already using
     *
     * @var array|string[]
     */
    private $groups = [];
    
    /**
     * Birthday if available
     *
     * @var null|DateTime
     */
    private $birthday = null;
    
    /**
     * Entity status if this is a @see Participant
     *
     * @var null|ParticipantStatus
     */
    private $status = null;
    
    /**
     * GroupFieldAssignEntityChoiceOption constructor.
     *
     * @param Event $event
     * @param int $id
     * @param string $nameFirst
     * @param string $nameLast
     * @param array|string[] $groups
     * @param DateTime|null $birthday
     * @param ParticipantStatus|null $status
     */
    public function __construct(
        Event $event,
        int $id,
        string $nameFirst,
        string $nameLast,
        $groups = [],
        ?DateTime $birthday = null,
        ?ParticipantStatus $status = null
    )
    {
        $this->event     = $event;
        $this->id        = $id;
        $this->nameFirst = $nameFirst;
        $this->nameLast  = $nameLast;
        $this->groups    = $groups;
        $this->birthday  = $birthday;
        $this->status    = $status;
    }
    
    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }
    
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * @return string
     */
    public function getNameFirst(): string
    {
        return $this->nameFirst;
    }
    
    /**
     * @return string
     */
    public function getNameLast(): string
    {
        return $this->nameLast;
    }
    
    /**
     * @return array|string[]
     */
    public function getGroups()
    {
        return $this->groups;
    }
    
    /**
     * Has groups
     *
     * @return bool
     */
    public function hasGroups(): bool
    {
        return count($this->groups) > 0;
    }
    
    /**
     * @return DateTime|null
     */
    public function getBirthday(): ?DateTime
    {
        return $this->birthday;
    }
    
    /**
     * @return ParticipantStatus|null
     */
    public function getStatus(): ?ParticipantStatus
    {
        return $this->status;
    }
    
    /**
     * Textual representation of option
     *
     * @return string
     */
    public function __toString()
    {
        $title = $this->nameLast;
        
        if ($this->nameFirst) {
            $title .= ', ' . $this->nameFirst;
        }
        
        if ($this->birthday) {
            $title .= ' (' . EventRepository::yearsOfLife($this->birthday, $this->event->getStartDate()) . ')';
        }
        if (count($this->groups)) {
            $title .= ' [' . implode(', ', $this->groups) . ']';
        }
        $title = htmlspecialchars($title, ENT_NOQUOTES);
        return $title;
    }
}
