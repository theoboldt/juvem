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
     * @ORM\Column(type="string", name="disable_token")
     * @ORM\GeneratedValue(strategy="UUID")
     * @Assert\NotBlank()
     */
    protected $disableToken;

    /**
     * @ORM\Column(type="boolean", name="has_age_relevant")
     */
    protected $hasAgeRelevant;

    /**
     * @ORM\Column(type="boolean", name="has_topic_child")
     */
    protected $hasTopicChild;

    /**
     * @ORM\Column(type="boolean", name="has_topic_teen")
     */
    protected $hasTopicTeen;

    /**
     * Contains a list of events the recipient of this emails wants to have
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Event", mappedBy="subscribedByNewsletterRecipient", cascade={"persist"})
     */
    protected $subscribedEvents;

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
     * Set hasAgeRelevant
     *
     * @param boolean $hasAgeRelevant
     *
     * @return NewsletterRecipient
     */
    public function setHasAgeRelevant($hasAgeRelevant)
    {
        $this->hasAgeRelevant = $hasAgeRelevant;

        return $this;
    }

    /**
     * Get hasAgeRelevant
     *
     * @return boolean
     */
    public function getHasAgeRelevant()
    {
        return $this->hasAgeRelevant;
    }

    /**
     * Set hasTopicChild
     *
     * @param boolean $hasTopicChild
     *
     * @return NewsletterRecipient
     */
    public function setHasTopicChild($hasTopicChild)
    {
        $this->hasTopicChild = $hasTopicChild;

        return $this;
    }

    /**
     * Get hasTopicChild
     *
     * @return boolean
     */
    public function hasTopicChild()
    {
        return $this->hasTopicChild;
    }

    /**
     * Set hasTopicTeen
     *
     * @param boolean $hasTopicTeen
     *
     * @return NewsletterRecipient
     */
    public function setHasTopicTeen($hasTopicTeen)
    {
        $this->hasTopicTeen = $hasTopicTeen;

        return $this;
    }

    /**
     * Get hasTopicTeen
     *
     * @return boolean
     */
    public function hasTopicTeen()
    {
        return $this->hasTopicTeen;
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
}
