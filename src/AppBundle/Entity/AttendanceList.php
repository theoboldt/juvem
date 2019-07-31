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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;

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
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AttendanceListParticipantFillout", mappedBy="attendanceList", cascade={"remove"})
     */
    protected $fillouts;

    /**
     * @ORM\ManyToMany(targetEntity="AttendanceListColumn", inversedBy="lists")
     * @ORM\JoinTable(name="attendance_list_column_assignments",
     *      joinColumns={@ORM\JoinColumn(name="list_id", referencedColumnName="tid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="column_id", referencedColumnName="column_id",
     *      onDelete="CASCADE")})
     * @var array|Collection|AttendanceList[]
     */
    protected $columns;
    
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $title;
    
    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Type("\DateTime")
     * @var \DateTime|null
     */
    protected $startDate;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fillouts = new ArrayCollection();
        $this->columns  = new ArrayCollection();
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
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }
    
    /**
     * @param \DateTime|null $startDate
     * @return AttendanceList
     */
    public function setStartDate(?\DateTime $startDate = null)
    {
        if ($startDate) {
            $this->startDate = clone $startDate;
            $this->startDate->setTime(10, 0, 0);
        } else {
            $this->startDate = null;
        }
        return $this;
    }

    
    
    /**
     * @return Collection|array|AttendanceListColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
    }
    
    /**
     * Determine if list contains column
     *
     * @param AttendanceListColumn $column
     * @return bool
     */
    public function hasColumn(AttendanceListColumn $column): bool
    {
        return $this->columns->contains($column);
    }
    
    /**
     * @param Collection|array|AttendanceListColumn[] $columns
     * @return AttendanceList
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }
    
    /**
     * Add column
     *
     * @param AttendanceListColumn $column
     *
     * @return AttendanceList
     */
    public function addColumn(AttendanceListColumn $column)
    {
        $this->columns->add($column);
        if (!$column->hasList($this)) {
            $column->addList($this);
        }

        return $this;
    }

    /**
     * Remove column
     *
     * @param AttendanceListColumn $column
     */
    public function removeColumn(AttendanceListColumn $column)
    {
        $this->columns->removeElement($column);
    }

    /**
     * Add fillout
     *
     * @param AttendanceListParticipantFillout $fillout
     *
     * @return AttendanceList
     */
    public function addFillout(AttendanceListParticipantFillout $fillout)
    {
        $this->fillouts[] = $fillout;
        if ($fillout->getAttendanceList() !== $this) {
            $fillout->setAttendanceList($this);
        }

        return $this;
    }

    /**
     * Remove fillout
     *
     * @param AttendanceListParticipantFillout $fillout
     */
    public function removeFillout(AttendanceListParticipantFillout $fillout)
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
