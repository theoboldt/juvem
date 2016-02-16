<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="`user`")
 */
class User extends BaseUser
{
    use HumanTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="uid")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Contains the participations assigned to this event
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Event", mappedBy="assignedUser", cascade={"persist"})
     */
    protected $assignedEvents;

    /**
     * Contains the participations assigned to this event
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Participation", mappedBy="assignedUser", cascade={"persist"})
     */
    protected $assignedParticipations;

    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        parent::__construct();

        $this->assignedEvents         = new ArrayCollection();
        $this->assignedParticipations = new ArrayCollection();
    }


    /**
     * @see getUid()
     * @return integer
     */
    public function getId()
    {
        return $this->getUid();
    }

    /**
     * @return integer
     */
    public function getUid()
    {
        return $this->id;
    }

    /**
     * Set email of this user
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $email = is_null($email) ? '' : $email;
        parent::setEmail($email);
        $this->setUsername($email);

        return $this;
    }

    /**
     * Add assignedEvent
     *
     * @param \AppBundle\Entity\Event $assignedEvent
     *
     * @return User
     */
    public function addAssignedEvent(\AppBundle\Entity\Event $assignedEvent)
    {
        $this->assignedEvents[] = $assignedEvent;

        return $this;
    }

    /**
     * Remove assignedEvent
     *
     * @param \AppBundle\Entity\Event $assignedEvent
     */
    public function removeAssignedEvent(\AppBundle\Entity\Event $assignedEvent)
    {
        $this->assignedEvents->removeElement($assignedEvent);
    }

    /**
     * Get assignedEvents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssignedEvents()
    {
        return $this->assignedEvents;
    }

    /**
     * Add assignedParticipation
     *
     * @param \AppBundle\Entity\Participation $assignedParticipation
     *
     * @return User
     */
    public function addAssignedParticipation(\AppBundle\Entity\Participation $assignedParticipation)
    {
        $this->assignedParticipations[] = $assignedParticipation;

        return $this;
    }

    /**
     * Remove assignedParticipation
     *
     * @param \AppBundle\Entity\Participation $assignedParticipation
     */
    public function removeAssignedParticipation(\AppBundle\Entity\Participation $assignedParticipation)
    {
        $this->assignedParticipations->removeElement($assignedParticipation);
    }

    /**
     * Get assignedParticipations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssignedParticipations()
    {
        return $this->assignedParticipations;
    }
}
