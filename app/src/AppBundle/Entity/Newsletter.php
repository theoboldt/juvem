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

use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\NewsletterRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="newsletter")
 */
class Newsletter extends NewsletterAbstract implements ProvidesModifiedInterface, ProvidesCreatedInterface
{
    use CreatedModifiedTrait;

    /**
     * @ORM\Column(type="integer", name="lid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $lid;

    /**
     * @ORM\Column(type="datetime", name="sent_at", nullable=true)
     */
    protected $sentAt = null;

    /**
     * @ORM\Column(type="string", length=128, name="subject")
     * @Assert\NotBlank()
     */
    protected $subject;

    /**
     * @ORM\Column(type="text", name="title")
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @ORM\Column(type="text", name="lead", nullable=true)
     */
    protected $lead = null;

    /**
     * @ORM\Column(type="text", name="content")
     * @Assert\NotBlank()
     */
    protected $content;

    /**
     * Contains a list of events which related to the topic of this newsletter
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Event")
     * @ORM\JoinTable(name="newsletter_event",
     *      joinColumns={@ORM\JoinColumn(name="lid", referencedColumnName="lid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="CASCADE")})
     */
    protected $events;

    /**
     * Contains a list of subscriptions which received this newsletter
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\NewsletterSubscription")
     * @ORM\JoinTable(name="newsletter_subscription_sent",
     *      joinColumns={@ORM\JoinColumn(name="lid", referencedColumnName="lid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="rid", referencedColumnName="rid", onDelete="CASCADE")})
     */
    protected $recipients;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->events        = new ArrayCollection();
        $this->recipients    = new ArrayCollection();
        $this->ageRangeBegin = 6;
        $this->ageRangeEnd   = 13;
        parent::__construct();
    }

    /**
     * Get lid
     *
     * @return integer
     */
    public function getLid()
    {
        return $this->lid;
    }

    /**
     * Set sentAt
     *
     * @param \DateTime $sentAt
     *
     * @return Newsletter
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get sentAt
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set subject
     *
     * @param string $subject
     *
     * @return Newsletter
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Newsletter
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set lead
     *
     * @param string $lead
     *
     * @return Newsletter
     */
    public function setLead($lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead
     *
     * @return string
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Newsletter
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Add recipient
     *
     * @param NewsletterSubscription $recipient
     *
     * @return Newsletter
     */
    public function addRecipient(NewsletterSubscription $recipient)
    {
        $this->recipients[] = $recipient;

        return $this;
    }

    /**
     * Remove recipient
     *
     * @param NewsletterSubscription $recipient
     */
    public function removeRecipient(NewsletterSubscription $recipient)
    {
        $this->recipients->removeElement($recipient);
    }

    /**
     * Get recipients
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

}
