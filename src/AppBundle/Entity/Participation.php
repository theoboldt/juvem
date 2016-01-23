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
    /**
     * @ORM\Column(type="integer", name="paid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $paid;

    /**
     * @ORM\Column(type="string", length=64, name="salution")
     */
    protected $parentSalution;

    /**
     * @ORM\Column(type="string", length=128, name="name_first")
     */
    protected $nameFirst;

    /**
     * @ORM\Column(type="string", length=128, name="name_last")
     * @Assert\NotBlank()
     */
    protected $nameLast;

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
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PhoneNumber", mappedBy="pnid")
     */
    protected $phoneNumbers;

    public function __construct()
    {
        $this->phoneNumbers = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtNow()
    {
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
     * Get paid
     *
     * @return integer
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set parentSalution
     *
     * @param string $parentSalution
     *
     * @return ParticipationType
     */
    public function setParentSalution($parentSalution)
    {
        $this->parentSalution = $parentSalution;

        return $this;
    }

    /**
     * Get parentSalution
     *
     * @return string
     */
    public function getParentSalution()
    {
        return $this->parentSalution;
    }

    /**
     * Set nameFirst
     *
     * @param string $nameFirst
     *
     * @return ParticipationType
     */
    public function setNameFirst($nameFirst)
    {
        $this->nameFirst = $nameFirst;

        return $this;
    }

    /**
     * Get nameFirst
     *
     * @return string
     */
    public function getNameFirst()
    {
        return $this->nameFirst;
    }

    /**
     * Set nameLast
     *
     * @param string $nameLast
     *
     * @return ParticipationType
     */
    public function setNameLast($nameLast)
    {
        $this->nameLast = $nameLast;

        return $this;
    }

    /**
     * Get nameLast
     *
     * @return string
     */
    public function getNameLast()
    {
        return $this->nameLast;
    }

    /**
     * Set addressStreet
     *
     * @param string $addressStreet
     *
     * @return ParticipationType
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
     * @return ParticipationType
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
     * @return ParticipationType
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
     * @return ParticipationType
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
     * @param \AppBundle\Entity\PhoneNumber $phoneNumber
     *
     * @return Participation
     */
    public function addPhoneNumber(\AppBundle\Entity\PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers[] = $phoneNumber;

        return $this;
    }

    /**
     * Remove phoneNumber
     *
     * @param \AppBundle\Entity\PhoneNumber $phoneNumber
     */
    public function removePhoneNumber(\AppBundle\Entity\PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers->removeElement($phoneNumber);
    }
}
