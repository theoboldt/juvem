<?php
namespace AppBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="participation")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 */
class Participation
{
    use HumanTrait;

    /**
     * @ORM\Column(type="integer", name="pid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $pid;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Event", mappedBy="eid")
     * @ORM\Column(type="integer", name="eid")
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
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\User", mappedBy="uid")
     * @ORM\Column(type="integer", name="created_by", nullable=true)
     */
    protected $createdBy = null;

    /**
     * @ORM\Column(type="datetime", name="modified_at")
     */
    protected $modifiedAt;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\User", mappedBy="uid")
     * @ORM\Column(type="integer", name="modified_by", nullable=true)
     */
    protected $modifiedBy = null;

    /**
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     */
    protected $deletedAt = null;

    /**
     * Contains the phone numbers assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PhoneNumber", cascade={"persist"}, mappedBy="nid")
     */
    protected $phoneNumbers;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Participant", cascade={"persist"}, mappedBy="aid")
     */
    protected $participants;


    public function __construct()
    {
        $this->phoneNumbers = new ArrayCollection();
        $this->addPhoneNumber(new PhoneNumber());

        $this->participants = new ArrayCollection();
        $this->addParticipant(new Participant());

        $this->modifiedAt = new \DateTime();
        $this->createdAt = new \DateTime();
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
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Event
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

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
     * @param string salution
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
     * Set createdBy
     *
     * @param integer $createdBy
     *
     * @return Participation
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy
     *
     * @param integer $modifiedBy
     *
     * @return Participation
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return integer
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set event
     *
     * @param integer $event
     *
     * @return Participation
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return integer
     */
    public function getEvent()
    {
        return $this->event;
    }
}
