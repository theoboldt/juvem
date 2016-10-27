<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="newsletter_subscription")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\NewsletterSubscriptionRepository")
 */
class NewsletterSubscription
{

    /**
     * Contains the lower limit of possible values for age range
     */
    const AGE_RANGE_MIN = 0;

    /**
     * Contains the upper limit of possible values for age range
     */
    const AGE_RANGE_MAX = 18;

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
     * @ORM\Column(type="string", length=128, name="name_last", nullable=true)
     */
    protected $nameLast = null;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="assignedNewsletterSubscription")
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
     * @ORM\Column(name="is_confirmed", type="boolean", options={"unsigned":true, "default":0})
     *
     * @var boolean
     */
    protected $isConfirmed = false;

    /**
     * @ORM\Column(type="string", name="disable_token", length=43)
     */
    protected $disableToken = '';

    /**
     * @ORM\Column(type="date", name="base_age", nullable=true)
     */
    protected $baseAge;

    /**
     * @ORM\Column(type="smallint", name="age_range_begin", options={"unsigned"=true})
     */
    protected $ageRangeBegin = self::AGE_RANGE_DEFAULT_MIN;

    /**
     * @ORM\Column(type="smallint", name="age_range_end", options={"unsigned"=true})
     */
    protected $ageRangeEnd = self::AGE_RANGE_DEFAULT_MAX;

    /**
     * Contains newsletter recipients which want to be informed about similar events like this
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Event")
     * @ORM\JoinTable(name="event_newsletter_subscription",
     *      joinColumns={@ORM\JoinColumn(name="rid", referencedColumnName="rid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="eid", referencedColumnName="eid",
     *      onDelete="CASCADE")})
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
     * @return NewsletterSubscription
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
     * Set nameLast
     *
     * @param string $nameLast
     *
     * @return NewsletterSubscription
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
     * Set disableToken
     *
     * @param string $disableToken
     *
     * @return NewsletterSubscription
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
     * @return NewsletterSubscription
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
     * @return NewsletterSubscription
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
     * @return NewsletterSubscription
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
        return $this->applyAging($this->ageRangeBegin);
    }

    /**
     * Set ageRangeEnd
     *
     * @param integer $ageRangeEnd
     *
     * @return NewsletterSubscription
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
        return $this->applyAging($this->ageRangeEnd);
    }

    /**
     * Set baseAge
     *
     * @param \DateTime $baseAge
     *
     * @return NewsletterSubscription
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
     * Find out wether aging is used or not
     *
     * @return bool
     */
    public function useAging()
    {
        return (bool)$this->getBaseAge();
    }

    /**
     * Define if aging should be used or not
     *
     * @param $value
     * @return NewsletterSubscription
     */
    public function setUseAging($value)
    {
        if ($value) {
            $this->setBaseAge(new \DateTime());
        } else {
            $this->setBaseAge(null);
        }
        return $this;
    }

    /**
     * Apply aging to transmitted age
     *
     * @param   integer $age
     * @return  number
     */
    public function applyAging($age)
    {
        $baseAge = $this->getBaseAge();
        if ($baseAge) {
            $today    = new \DateTime();
            $interval = $today->diff($baseAge);
            $age += abs($interval->format('%y'));
        }

        if ($age < self::AGE_RANGE_MIN) {
            return self::AGE_RANGE_MIN;
        }
        if ($age > self::AGE_RANGE_MAX) {
            return self::AGE_RANGE_MAX;
        }

        return $age;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     *
     * @return NewsletterSubscription
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

    /**
     * Set isConfirmed
     *
     * @param boolean $isConfirmed
     *
     * @return NewsletterSubscription
     */
    public function setIsConfirmed($isConfirmed)
    {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    /**
     * Get isConfirmed
     *
     * @return boolean
     */
    public function getIsConfirmed()
    {
        return $this->isConfirmed;
    }

}
