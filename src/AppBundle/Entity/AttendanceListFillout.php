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
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="attendance_list_fillout")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AttendanceListFilloutRepository")
 */
class   AttendanceListFillout
{
    use CreatedModifiedTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $did;

    /**
     * @ORM\ManyToOne(targetEntity="AttendanceList", inversedBy="fillouts", cascade={"all"})
     * @ORM\JoinColumn(name="tid", referencedColumnName="tid", onDelete="cascade")
     */
    protected $attendanceList;

    /**
     * @ORM\ManyToOne(targetEntity="Participant", inversedBy="attendanceListsFillouts", cascade={"all"})
     * @ORM\JoinColumn(name="aid", referencedColumnName="aid", onDelete="cascade")
     */
    protected $participant;

    /**
     * @ORM\Column(type="boolean", name="is_attendant", nullable=true)
     */
    protected $isAttendant;

    /**
     * @ORM\Column(type="boolean", name="is_public_transport", nullable=true)
     */
    protected $isPublicTransport;

    /**
     * @ORM\Column(type="boolean", name="is_paid", nullable=true)
     */
    protected $isPaid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $comment;

    /**
     * Get did
     *
     * @return integer
     */
    public function getDid()
    {
        return $this->did;
    }

    /**
     * Set isPublicTransport
     *
     * @param boolean $isPublicTransport
     *
     * @return AttendanceListFillout
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
     * @return AttendanceListFillout
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
     * Set comment
     *
     * @param string $comment
     *
     * @return AttendanceListFillout
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set participant
     *
     * @param Participant $participant
     *
     * @return AttendanceListFillout
     */
    public function setParticipant(Participant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get participant
     *
     * @return Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set attendanceList
     *
     * @param AttendanceList $attendanceList
     *
     * @return AttendanceListFillout
     */
    public function setAttendanceList(AttendanceList $attendanceList = null)
    {
        $this->attendanceList = $attendanceList;

        return $this;
    }

    /**
     * Get attendanceList
     *
     * @return AttendanceList
     */
    public function getAttendanceList()
    {
        return $this->attendanceList;
    }

    /**
     * Set isAttendant
     *
     * @param boolean $isAttendant
     *
     * @return AttendanceListFillout
     */
    public function setIsAttendant($isAttendant)
    {
        $this->isAttendant = $isAttendant;

        return $this;
    }

    /**
     * Get isAttendant
     *
     * @return boolean
     */
    public function getIsAttendant()
    {
        return $this->isAttendant;
    }
}
