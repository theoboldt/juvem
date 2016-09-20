<?php
namespace AppBundle\Entity;

use AppBundle\BitMask\ParticipantStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="participation")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 */
class Participation
{
    use HumanTrait, AcquisitionAttributeFilloutTrait;

    /**
     * @ORM\Column(type="integer", name="pid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $pid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event", inversedBy="participations", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     */
    protected $event;

    /**
     * @ORM\Column(type="string", length=64, name="salution")
     */
    protected $salution;

    /**
     * @ORM\Column(type="string", length=128, name="address_street")
     */
    protected $addressStreet;

    /**
     * @ORM\Column(type="string", length=128, name="address_city")
     */
    protected $addressCity;

    /**
     * @ORM\Column(type="string", length=16, name="address_zip")
     */
    protected $addressZip;

    /**
     * @ORM\Column(type="string", length=128, name="email")
     */
    protected $email;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", name="modified_at")
     */
    protected $modifiedAt;

    /**
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     */
    protected $deletedAt = null;

    /**
     * Contains the phone numbers assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PhoneNumber", cascade={"all"}, mappedBy="participation")
     */
    protected $phoneNumbers;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Participant", cascade={"all"}, mappedBy="participation")
     */
    protected $participants;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AcquisitionAttributeFillout", cascade={"all"}, mappedBy="participation")
     */
    protected $acquisitionAttributeFillouts;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="assignedParticipations")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL")
     */
    protected $assignedUser;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->phoneNumbers = new ArrayCollection();
        $phoneNumber    = new PhoneNumber();
        $phoneNumber->setParticipation($this);
        $this->addPhoneNumber($phoneNumber);

        $this->participants = new ArrayCollection();
        $participant    = new Participant();
        $participant->setParticipation($this);
        $this->addParticipant($participant);

        $this->acquisitionAttributeFillouts = new ArrayCollection();

        $this->modifiedAt = new \DateTime();
        $this->createdAt  = new \DateTime();
    }

    /**
     * Print the name, including first name if available
     *
     * @return string
     */
    public function getName()
    {
        $name = $this->getNameLast();

        if ($this->getNameFirst()) {
            $name .= ', ' . $this->getNameFirst();
        }

        return $name;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Event
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setModifiedAtNow()
    {
        $this->modifiedAt = new \DateTime();
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     *
     * @return Event
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set deletedAt, cascading to participants
     *
     * @param \DateTime $deletedAt
     *
     * @return Event
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $participant->setDeletedAt($deletedAt);
        }

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Get pid
     *
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set salution
     *
     * @param string $parentSalution Salution
     *
     * @return Participation
     */
    public function setSalution($parentSalution)
    {
        $this->salution = $parentSalution;

        return $this;
    }

    /**
     * Get salution
     *
     * @return string
     */
    public function getSalution()
    {
        return $this->salution;
    }


    /**
     * Set addressStreet
     *
     * @param string $addressStreet
     *
     * @return Participation
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    /**
     * Get addressStreet
     *
     * @return string
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * Set addressCity
     *
     * @param string $addressCity
     *
     * @return Participation
     */
    public function setAddressCity($addressCity)
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    /**
     * Get addressCity
     *
     * @return string
     */
    public function getAddressCity()
    {
        return $this->addressCity;
    }

    /**
     * Set addressZip
     *
     * @param string $addressZip
     *
     * @return Participation
     */
    public function setAddressZip($addressZip)
    {
        $this->addressZip = $addressZip;

        return $this;
    }

    /**
     * Get addressZip
     *
     * @return string
     */
    public function getAddressZip()
    {
        return $this->addressZip;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Participation
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get phone Numbers
     */
    public function getPhoneNumbers()
    {
        return $this->phoneNumbers;
    }

    /**
     * Add phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     *
     * @return Participation
     */
    public function addPhoneNumber(PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers[] = $phoneNumber;

        return $this;
    }

    /**
     * Remove phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     */
    public function removePhoneNumber(PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers->removeElement($phoneNumber);
    }

    /**
     * Add participant
     *
     * @param \AppBundle\Entity\Participant $participant
     *
     * @return Participation
     */
    public function addParticipant(Participant $participant)
    {
        $this->participants[] = $participant;

        return $this;
    }

    /**
     * Remove participant
     *
     * @param \AppBundle\Entity\Participant $participant
     */
    public function removeParticipant(Participant $participant)
    {
        $this->participants->removeElement($participant);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Set event
     *
     * @param Event $event
     *
     * @return Participation
     */
    public function setEvent(Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set assignedUser
     *
     * @param \AppBundle\Entity\User $assignedUser
     *
     * @return Participation
     */
    public function setAssignedUser(\AppBundle\Entity\User $assignedUser = null)
    {
        $this->assignedUser = $assignedUser;

        return $this;
    }

    /**
     * Get assignedUser
     *
     * @return \AppBundle\Entity\User
     */
    public function getAssignedUser()
    {
        return $this->assignedUser;
    }

    /**
     * Check if all related participants are confirmed
     *
     * @return bool
     */
    public function isConfirmed()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_CONFIRMED)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set conformation value for all related participants
     *
     * @param   bool $confirmed New value
     * @return bool
     */
    public function setIsConfirmed($confirmed = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($confirmed) {
                $status->enable(ParticipantStatus::TYPE_STATUS_CONFIRMED);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_CONFIRMED);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

    /**
     * Check if all related participants are paid
     *
     * @return bool
     */
    public function isPaid()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_PAID)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set conformation value for all related participants
     *
     * @param   bool $paid New value
     * @return bool
     */
    public function setIsPaid($paid = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($paid) {
                $status->enable(ParticipantStatus::TYPE_STATUS_PAID);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_PAID);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

    /**
     * Check if all related participants are withdrawn
     *
     * @return bool
     */
    public function isWithdrawn()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set conformation value for all related participants
     *
     * @param   bool $withdrawn New value
     * @return self
     */
    public function setIsWithdrawn($withdrawn = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($withdrawn) {
                $status->enable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

}
