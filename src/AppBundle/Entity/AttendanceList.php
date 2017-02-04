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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="attendance_list")
 */
class AttendanceList
{
    use CreatedModifiedTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $tid;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="attendanceLists", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     */
    protected $event;

    /**
     * @ORM\OneToMany(targetEntity="AttendanceListFillout", mappedBy="attendanceList", cascade={"remove"})
     */
    protected $fillouts;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @ORM\Column(type="boolean", name="is_public_transport")
     */
    protected $isPublicTransport = false;

    /**
     * @ORM\Column(type="boolean", name="is_paid")
     */
    protected $isPaid = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fillouts = new ArrayCollection();
    }

    /**
     * Get tid
     *
     * @return integer
     */
    public function getTid()
    {
        return $this->tid;
    }

    /**
     * Set event
     *
     * @param Event $event
     *
     * @return AttendanceList
     */
    public function setEvent(Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return AttendanceList
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
     * Set isPublicTransport
     *
     * @param boolean $isPublicTransport
     *
     * @return AttendanceList
     */
    public function setIsPublicTransport($isPublicTransport)
    {
        $this->isPublicTransport = $isPublicTransport;

        return $this;
    }

    /**
     * Get isPublicTransport
     *
     * @return boolean
     */
    public function getIsPublicTransport()
    {
        return $this->isPublicTransport;
    }

    /**
     * Set isPaid
     *
     * @param boolean $isPaid
     *
     * @return AttendanceList
     */
    public function setIsPaid($isPaid)
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    /**
     * Get isPaid
     *
     * @return boolean
     */
    public function getIsPaid()
    {
        return $this->isPaid;
    }

    /**
     * Add fillout
     *
     * @param AttendanceListFillout $fillout
     *
     * @return AttendanceList
     */
    public function addFillout(AttendanceListFillout $fillout)
    {
        $this->fillouts[] = $fillout;

        return $this;
    }

    /**
     * Remove fillout
     *
     * @param AttendanceListFillout $fillout
     */
    public function removeFillout(AttendanceListFillout $fillout)
    {
        $this->fillouts->removeElement($fillout);
    }

    /**
     * Get fillouts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFillouts()
    {
        return $this->fillouts;
    }

}
