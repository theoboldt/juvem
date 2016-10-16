<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="newsletter_recipient")
 */
class NewsletterRecipient
{

    /**
     * Contains the lower limit of default value for age range
     */
    const AGE_RANGE_DEFAULT_MIN = 6;

    /**
     * Contains the upper limit of default value for age range
     */
    const AGE_RANGE_DEFAULT_MAX = 16;

    /**
     * @ORM\Column(type="integer", name="rid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $rid;

    /**
     * @ORM\Column(type="string", length=128, name="email")
     */
    protected $email;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="assignedNewsletter")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL", nullable=true)
     */
    protected $assignedUser;

    /**
     * @ORM\Column(name="is_enabled", type="boolean", options={"unsigned":true,"default":1})
     *
     * @var boolean
     */
    protected $isEnabled = true;

    /**
     * @ORM\Column(type="string", name="disable_token", length=36)
     * @  xOR xM\GeneratedValue(strategy="UUID")
     */
    protected $disableToken = '';

    /**
     * @ORM\Column(type="date", name="base_age", nullable=true)
     */
    protected $baseAge;

    /**
     * @ORM\Column(type="smallint", name="age_range_begin", options={"unsigned"=true})
     */
    protected $ageRangeBegin;

    /**
     * @ORM\Column(type="smallint", name="age_range_end", options={"unsigned"=true})
     */
    protected $ageRangeEnd;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Event", mappedBy="subscribedByNewsletterRecipients")
     */
    private $subscribedEvents;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->subscribedEvents = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get rid
     *
     * @return integer
     */
    public function getRid()
    {
        return $this->rid;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return NewsletterRecipient
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
     * Set disableToken
     *
     * @param string $disableToken
     *
     * @return NewsletterRecipient
     */
    public function setDisableToken($disableToken)
    {
        $this->disableToken = $disableToken;

        return $this;
    }

    /**
     * Get disableToken
     *
     * @return string
     */
    public function getDisableToken()
    {
        return $this->disableToken;
    }

    /**
     * Set assignedUser
     *
     * @param \AppBundle\Entity\User $assignedUser
     *
     * @return NewsletterRecipient
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
     * Add subscribedEvent
     *
     * @param \AppBundle\Entity\Event $subscribedEvent
     *
     * @return NewsletterRecipient
     */
    public function addSubscribedEvent(\AppBundle\Entity\Event $subscribedEvent)
    {
        $this->subscribedEvents[] = $subscribedEvent;

        return $this;
    }

    /**
     * Remove subscribedEvent
     *
     * @param \AppBundle\Entity\Event $subscribedEvent
     */
    public function removeSubscribedEvent(\AppBundle\Entity\Event $subscribedEvent)
    {
        $this->subscribedEvents->removeElement($subscribedEvent);
    }

    /**
     * Get subscribedEvents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubscribedEvents()
    {
        return $this->subscribedEvents;
    }

    /**
     * Set ageRangeBegin
     *
     * @param integer $ageRangeBegin
     *
     * @return NewsletterRecipient
     */
    public function setAgeRangeBegin($ageRangeBegin)
    {
        $this->ageRangeBegin = $ageRangeBegin;

        return $this;
    }

    /**
     * Get ageRangeBegin
     *
     * @return integer
     */
    public function getAgeRangeBegin()
    {
        return $this->ageRangeBegin;
    }

    /**
     * Set ageRangeEnd
     *
     * @param integer $ageRangeEnd
     *
     * @return NewsletterRecipient
     */
    public function setAgeRangeEnd($ageRangeEnd)
    {
        $this->ageRangeEnd = $ageRangeEnd;

        return $this;
    }

    /**
     * Get ageRangeEnd
     *
     * @return integer
     */
    public function getAgeRangeEnd()
    {
        return $this->ageRangeEnd;
    }

    /**
     * Set baseAge
     *
     * @param \DateTime $baseAge
     *
     * @return NewsletterRecipient
     */
    public function setBaseAge($baseAge)
    {
        $this->baseAge = $baseAge;

        return $this;
    }

    /**
     * Get baseAge
     *
     * @return \DateTime
     */
    public function getBaseAge()
    {
        return $this->baseAge;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     *
     * @return NewsletterRecipient
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return boolean
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }
}
