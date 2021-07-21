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
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Mail\SupportsRelatedEmailsInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;

/**
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 * @ORM\Entity
 * @ORM\Table(name="`user`")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\UserRepository")
 */
class User extends BaseUser implements ProvidesModifiedInterface, ProvidesCreatedInterface, SpecifiesChangeTrackingStorableRepresentationInterface, HumanInterface, UserInterface, SupportsRelatedEmailsInterface
{
    use CreatedModifiedTrait, HumanTrait;

    const ROLE_ADMIN       = 'ROLE_ADMIN';
    const ROLE_ADMIN_LABEL = 'Administration';

    const ROLE_ADMIN_EVENT       = 'ROLE_ADMIN_EVENT';
    const ROLE_ADMIN_EVENT_LABEL = 'Veranstaltungsverwaltung (ausgewählte)';

    const ROLE_ADMIN_EVENT_GLOBAL       = 'ROLE_ADMIN_EVENT_GLOBAL';
    const ROLE_ADMIN_EVENT_GLOBAL_LABEL = 'Veranstaltungsverwaltung (alle)';

    const ROLE_ADMIN_USER       = 'ROLE_ADMIN_USER';
    const ROLE_ADMIN_USER_LABEL = 'Benutzerverwaltung';

    const ROLE_ADMIN_NEWSLETTER       = 'ROLE_ADMIN_NEWSLETTER';
    const ROLE_ADMIN_NEWSLETTER_LABEL = 'Newsletterverwaltung';

    const ROLE_EMPLOYEE = 'ROLE_EMPLOYEE_LABEL';
    const ROLE_EMPLOYEE_LABEL = 'Mitarbeiterverwaltung';

    const ROLE_CLOUD = 'ROLE_CLOUD';
    const ROLE_CLOUD_LABEL = 'Cloud-Zugang';

    /**
     * @Serialize\Expose
     * @Serialize\Type("integer")
     * @ORM\Id
     * @ORM\Column(type="integer", name="uid")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime", name="created_at", options={"default" : "2017-01-01 12:00:00"})
     */
    protected $createdAt;

    /**
     * Contains the participations assigned to this event
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Participation", mappedBy="assignedUser", cascade={"persist"})
     */
    protected $assignedParticipations;
    
    /**
     * Contains the employees assigned to this event
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Employee", mappedBy="assignedUser", cascade={"persist"})
     */
    protected $assignedEmployees;

    /**
     * @var \Doctrine\Common\Collections\Collection|Event[]
     *
     * @ORM\ManyToMany(targetEntity="Event", inversedBy="subscribers", cascade={"persist"})
     * @ORM\JoinTable(
     *  name="user_event_subscription",
     *  joinColumns={@ORM\JoinColumn(referencedColumnName="uid")},
     *  inverseJoinColumns={@ORM\JoinColumn(referencedColumnName="eid")}
     * )
     */
    protected $subscribedEvents;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\EventUserAssignment", mappedBy="user", cascade={"remove"})
     * @var \Doctrine\Common\Collections\Collection|EventUserAssignment[]
     */
    protected $eventAssignments;

    /**
     * @ORM\Column(type="string", length=40, options={"fixed" = true}, name="settings_hash")
     */
    protected $settingsHash = 'bf21a9e8fbc5a3846fb05b4fa0859e0917b2202f';

    /**
     * @ORM\Column(type="text", name="settings")
     */
    protected $settings = '{}';

    /**
     * Defines if help buttons should be excluded from tabindex in forms for this user
     *
     * @ORM\Column(type="boolean", name="is_exclude_help_tabindex", options={"default" : "0"})
     * @var bool
     */
    protected $isExcludeHelpTabindex = false;
    
    /**
     * If this user has a cloud account, the related username is stored here
     *
     * @ORM\Column(type="string", name="cloud_username", length=255, nullable=true)
     * @var null|string
     */
    private $cloudUsername = null;

    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        parent::__construct();

        $this->assignedParticipations = new ArrayCollection();
        $this->subscribedEvents       = new ArrayCollection();

        //ensure created is stored if pre persist annotation does not work
        if (!$this->createdAt) {
            $this->setCreatedAtNow();
        }
    }


    /**
     * @see getUid()
     * @return integer
     */
    public function getId(): ?int
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
     * Define user enable/disabled state
     *
     * @see setLocked()
     * @param bool $boolean Enable/disabled  state
     * @return $this
     */
    public function setIsEnabled($boolean)
    {
        $this->setEnabled($boolean);

        return $this;
    }

    /**
     * Add assignedParticipation
     *
     * @param Participation $assignedParticipation
     *
     * @return User
     */
    public function addAssignedParticipation(Participation $assignedParticipation)
    {
        $this->assignedParticipations[] = $assignedParticipation;

        return $this;
    }

    /**
     * Remove assignedParticipation
     *
     * @param Participation $assignedParticipation
     */
    public function removeAssignedParticipation(Participation $assignedParticipation)
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


    /**
     * Set settingsHash
     *
     * @param string $settingsHash
     *
     * @return User
     */
    public function setSettingsHash($settingsHash)
    {
        $this->settingsHash = $settingsHash;

        return $this;
    }

    /**
     * Get settingsHash
     *
     * @return string
     */
    public function getSettingsHash()
    {
        return $this->settingsHash;
    }

    /**
     * Set settings
     *
     * @see setSettingsHash()
     * @param array|string $settings
     *
     * @return User
     */
    public function setSettings($settings)
    {
        if (is_array($settings)) {
            $settings = json_encode($settings);
        }
        $this->setSettingsHash(sha1($settings));
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings
     *
     * @param bool $decoded Set to true to get the result as array instead of json string
     * @return string|array
     */
    public function getSettings($decoded = false)
    {
        if ($decoded) {
            return json_decode($this->settings, true);
        }

        return $this->settings;
    }

    /**
     * Add event subscription
     *
     * @param Event $event
     * @return self
     */
    public function addSubscribedEvent(Event $event)
    {
        if (!$this->subscribedEvents->contains($event)) {
            $this->subscribedEvents->add($event);
            $event->addSubscriber($this);
        }
        return $this;
    }

    /**
     * Remove event subscription
     *
     * @param Event $event
     * @return self
     */
    public function removeSubscribedEvent(Event $event)
    {
        if ($this->subscribedEvents->contains($event)) {
            $this->subscribedEvents->removeElement($event);
            $event->removeSubscriber($this);
        }
        return $this;
    }

    /**
     * Get subscribedEvents
     *
     * @return \Doctrine\Common\Collections\Collection|Event[]
     */
    public function getSubscribedEvents()
    {
        return $this->subscribedEvents;
    }

    /**
     * @return EventUserAssignment[]|\Doctrine\Common\Collections\Collection
     */
    public function getEventAssignments()
    {
        return $this->eventAssignments;
    }

    /**
     * Get user full name
     *
     * @deprecated
     * @return string
     */
    public function userFullname() {
        return $this->fullname();
    }

    /**
     * Define if help buttons should be excluded from tabindex in forms for this user
     *
     * @return bool
     */
    public function isExcludeHelpTabindex(): bool
    {
        return $this->isExcludeHelpTabindex;
    }

    /**
     * Determine if help buttons should be excluded from tabindex in forms for this user
     *
     * @param bool $isExcludeHelpTabindex
     */
    public function setIsExcludeHelpTabindex(bool $isExcludeHelpTabindex): void
    {
        $this->isExcludeHelpTabindex = $isExcludeHelpTabindex;
    }
    
    /**
     * @return string|null
     */
    public function getCloudUsername(): ?string
    {
        return $this->cloudUsername;
    }
    
    /**
     * @param string|null $cloudUsername
     */
    public function setCloudUsername(?string $cloudUsername): void
    {
        $this->cloudUsername = $cloudUsername;
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingStorableRepresentation()
    {
        return sprintf('%s [%d]', $this->fullname(), $this->getId());
    }
}
