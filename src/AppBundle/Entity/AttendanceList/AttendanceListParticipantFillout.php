<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\AttendanceList;

use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Participant;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="attendance_list_participant_fillout")
 * @ORM\Entity(repositoryClass="AttendanceListFilloutParticipantRepository")
 */
class AttendanceListParticipantFillout
{
    use CreatedModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AttendanceList", inversedBy="fillouts", cascade={"all"})
     * @ORM\JoinColumn(name="list_id", referencedColumnName="tid", onDelete="cascade", nullable=false)
     * @var AttendanceList
     */
    protected $attendanceList;
    
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Participant", inversedBy="attendanceListsFillouts", cascade={"all"})
     * @ORM\JoinColumn(name="participant_id", referencedColumnName="aid", onDelete="cascade", nullable=false)
     * @var Participant
     */
    protected $participant;
    
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AttendanceListColumn", inversedBy="fillouts", cascade={"all"})
     * @ORM\JoinColumn(name="column_id", referencedColumnName="column_id", onDelete="cascade", nullable=false)
     * @var Participant
     */
    protected $column;
    
    /**
     * @ORM\ManyToOne(targetEntity="AttendanceListColumnChoice", cascade={"all"})
     * @ORM\JoinColumn(name="choice_id", referencedColumnName="choice_id", onDelete="cascade", nullable=false)
     * @var AttendanceListColumnChoice
     */
    protected $choice;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $comment = null;
    
    /**
     * AttendanceListParticipantFillout constructor.
     *
     * @param AttendanceList $attendanceList
     * @param Participant $participant
     * @param AttendanceListColumnChoice $choice
     * @param string|null $comment
     */
    public function __construct(
        AttendanceList $attendanceList,
        Participant $participant,
        AttendanceListColumnChoice $choice = null,
        ?string $comment = null
    )
    {
        $this->choice  = $choice;
        $this->comment = $comment;
        if ($attendanceList) {
            $attendanceList->addFillout($this);
        }
        if ($participant) {
            $participant->addAttendanceListsFillout($this);
        }
    }
    
    /**
     * @return AttendanceList|null
     */
    public function getAttendanceList(): ?AttendanceList
    {
        return $this->attendanceList;
    }
    
    /**
     * @param AttendanceList $attendanceList
     * @return AttendanceListParticipantFillout
     */
    public function setAttendanceList(AttendanceList $attendanceList): AttendanceListParticipantFillout
    {
        $this->attendanceList = $attendanceList;
        return $this;
    }
    
    /**
     * @return Participant|null
     */
    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }
    
    /**
     * @param Participant $participant
     * @return AttendanceListParticipantFillout
     */
    public function setParticipant(Participant $participant): AttendanceListParticipantFillout
    {
        $this->participant = $participant;
        return $this;
    }
    
    /**
     * @return AttendanceListColumnChoice
     */
    public function getChoice(): AttendanceListColumnChoice
    {
        return $this->choice;
    }
    
    /**
     * @param AttendanceListColumnChoice $choice
     * @return AttendanceListParticipantFillout
     */
    public function setChoice(AttendanceListColumnChoice $choice): AttendanceListParticipantFillout
    {
        $this->choice = $choice;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
    
    /**
     * @param string|null $comment
     * @return AttendanceListParticipantFillout
     */
    public function setComment(?string $comment): AttendanceListParticipantFillout
    {
        $this->comment = $comment;
        return $this;
    }
    
    
}
