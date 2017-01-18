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
class NewsletterSubscription extends NewsletterAbstract
{

    /**
     * @ORM\Column(type="integer", name="rid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $rid;

    /**
     * @ORM\Column(type="string", length=128, name="email")
     * @Assert\NotBlank()
     * @Assert\Email()
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
     * Contains newsletter recipients which want to be informed about similar events like this
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Event")
     * @ORM\JoinTable(name="event_newsletter_subscription",
     *      joinColumns={@ORM\JoinColumn(name="rid", referencedColumnName="rid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="eid", referencedColumnName="eid",
     *      onDelete="CASCADE")})
     */
    protected $events;

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
     * Get nameLast if set, returns email if not
     *
     * @return string
     */
    public function getName()
    {
        return $this->nameLast ? $this->nameLast : $this->email;
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
