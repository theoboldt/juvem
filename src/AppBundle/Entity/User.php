<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="`user`")
 */
class User extends BaseUser
{
    use HumanTrait;

    const ROLE_ADMIN       = 'ROLE_ADMIN';
    const ROLE_ADMIN_LABEL = 'Administration';

    const ROLE_ADMIN_EVENT       = 'ROLE_ADMIN_EVENT';
    const ROLE_ADMIN_EVENT_LABEL = 'Veranstaltungsverwaltung';

    const ROLE_ADMIN_USER       = 'ROLE_ADMIN_USER';
    const ROLE_ADMIN_USER_LABEL = 'Benutzerverwaltung';


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
     * @ORM\Column(type="string", length=40, options={"fixed" = true}, name="settings_hash")
     */
    protected $settingsHash;

    /**
     * @ORM\Column(type="text", name="settings")
     */
    protected $settings = '{}';

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
     * Set user locked
     *
     * @see setLocked()
     * @param bool $boolean Locked state
     * @return $this
     */
    public function setIsLocked($boolean)
    {
        $this->setLocked($boolean);

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
        $this->setLocked($boolean);

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

}
