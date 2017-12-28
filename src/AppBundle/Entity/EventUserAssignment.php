<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="event_user_assignment")
 */
class EventUserAssignment
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User", inversedBy="eventAssignments", cascade={"all"})
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="cascade")
     * @var User
     */
    protected $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="userAssignments", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     * @var Event
     */
    protected $event;

    /**
     * @ORM\Column(type="boolean", name="allowed_to_edit", nullable=false)
     * @var bool
     */
    protected $allowedToEdit = false;

    /**
     * @ORM\Column(type="boolean", name="allowed_to_read_comments", nullable=false)
     * @var bool
     */
    protected $allowedToReadComments = false;

    /**
     * @ORM\Column(type="boolean", name="allowed_to_comment", nullable=false)
     * @var bool
     */
    protected $allowedToComment = false;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return EventUserAssignment
     */
    public function setUser(User $user): EventUserAssignment
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @param Event $event
     * @return EventUserAssignment
     */
    public function setEvent(Event $event): EventUserAssignment
    {
        $this->event = $event;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowedToEdit(): bool
    {
        return $this->allowedToEdit;
    }

    /**
     * @param bool $allowedToEdit
     * @return EventUserAssignment
     */
    public function setAllowedToEdit(bool $allowedToEdit): EventUserAssignment
    {
        $this->allowedToEdit = $allowedToEdit;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowedToReadComments(): bool
    {
        return $this->allowedToReadComments;
    }

    /**
     * @param bool $allowedToReadComments
     * @return EventUserAssignment
     */
    public function setAllowedToReadComments(bool $allowedToReadComments): EventUserAssignment
    {
        $this->allowedToReadComments = $allowedToReadComments;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowedToComment(): bool
    {
        return $this->allowedToComment;
    }

    /**
     * @param bool $allowedToComment
     * @return EventUserAssignment
     */
    public function setAllowedToComment(bool $allowedToComment): EventUserAssignment
    {
        $this->allowedToComment = $allowedToComment;
        return $this;
    }



}
