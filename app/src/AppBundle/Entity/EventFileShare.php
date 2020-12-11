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

use Doctrine\ORM\Mapping as ORM;

/**
 * EventFileShare
 *
 * @ORM\Entity
 * @ORM\Table(name="event_file_share")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EventFileShareRepository")
 */
class EventFileShare
{
    const PURPOSE_TEAM       = 'team';
    const PURPOSE_MANAGEMENT = 'management';
    const PURPOSE_GALLERY    = 'gallery';
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="userAssignments", cascade={"persist"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     * @var Event
     */
    protected $event;
    
    /**
     * @ORM\Column(type="string", name="purpose", columnDefinition="ENUM('team', 'management', 'gallery')")
     * @var string
     */
    protected $purpose;
    
    /**
     * File id of related share
     *
     * @ORM\Column(type="integer", name="directory_id", options={"unsigned"=true})
     * @var int
     */
    protected $directoryId;
    
    /**
     * WebDAV href where directory can be reached
     *
     * @ORM\Column(type="string", length=255, name="directory_href")
     * @var string
     */
    protected $directoryHref;
    
    /**
     * Name of directory
     *
     * @ORM\Column(type="string", length=128, name="directory_name")
     * @var string
     */
    protected $directoryName;
    
    /**
     * If there is a project folder for the team created on connected cloud, related file name is set here
     *
     * @ORM\Column(type="string", length=128, name="group_name")
     * @var string
     */
    protected $groupName;
    
    /**
     * EventFileShare constructor.
     *
     * @param Event $event
     * @param string $purpose
     * @param int $directoryId
     * @param string $directoryHref
     * @param string $directoryName
     * @param string $groupName
     */
    public function __construct(
        Event $event, string $purpose, int $directoryId, string $directoryHref, string $directoryName, string $groupName
    )
    {
        $this->event         = $event;
        $this->directoryId   = $directoryId;
        $this->directoryHref = $directoryHref;
        $this->directoryName = $directoryName;
        $this->groupName     = $groupName;
        $this->setPurpose($purpose);
    }
    
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }
    
    /**
     * @return string
     */
    public function getPurpose(): string
    {
        return $this->purpose;
    }
    
    /**
     * @return int
     */
    public function getDirectoryId(): int
    {
        return $this->directoryId;
    }
    
    /**
     * @return string
     */
    public function getDirectoryHref(): string
    {
        return $this->directoryHref;
    }
    
    /**
     * @return string
     */
    public function getDirectoryName(): string
    {
        return $this->directoryName;
    }
    
    /**
     * @return string
     */
    public function getGroupName(): string
    {
        return $this->groupName;
    }
    
    /**
     * @param string $purpose
     */
    private function setPurpose(string $purpose): void
    {
        if (!in_array($purpose, [self::PURPOSE_TEAM, self::PURPOSE_MANAGEMENT, self::PURPOSE_GALLERY])) {
            throw new \InvalidArgumentException('Purpose ' . $purpose . ' is not supported');
        }
        $this->purpose = $purpose;
    }
}