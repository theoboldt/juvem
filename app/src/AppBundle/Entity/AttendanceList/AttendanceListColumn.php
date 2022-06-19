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

use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Table(name="attendance_list_column")
 * @ORM\Entity(repositoryClass="AttendanceListColumnRepository")
 */
class AttendanceListColumn implements SoftDeleteableInterface
{
    use SoftDeleteTrait;

    /**
     * @ORM\Column(type="integer", name="column_id", options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    protected $columnId;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @var string
     */
    protected $title;

    /**
     * List of possible choices
     *
     * @ORM\OneToMany(targetEntity="AttendanceListColumnChoice", mappedBy="column", cascade={"persist"})
     * @var Collection|array|AttendanceListColumnChoice[]
     */
    protected $choices;

    /**
     * @ORM\OneToMany(targetEntity="AttendanceListParticipantFillout", mappedBy="column", cascade={"remove"})
     */
    protected $fillouts;

    /**
     * List of columns
     *
     * @ORM\ManyToMany(targetEntity="AttendanceList", mappedBy="columns")
     */
    protected $lists;

    /**
     * AttendanceListColumn constructor.
     *
     * @param string $title
     */
    public function __construct(string $title)
    {
        $this->title   = $title;
        $this->choices = new ArrayCollection();
        $this->lists   = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getColumnId(): ?int
    {
        return $this->columnId;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return AttendanceListColumn
     */
    public function setTitle(string $title = null): AttendanceListColumn
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Determine if deleted choices are configured here
     *
     * @return bool
     */
    public function hasDeletedChoices(): bool
    {
        foreach ($this->choices as $choice) {
            if ($choice->isDeleted()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return AttendanceListColumnChoice[]
     */
    public function getChoices($includeDeleted = false)
    {
        if ($includeDeleted) {
            return $this->choices;
        } else {
            $choices = [];
            /** @var AttendanceListColumnChoice $choice */
            foreach ($this->choices as $choice) {
                if (!$choice->isDeleted()) {
                    $choices[] = $choice;
                }
            }
            return $choices;
        }
    }

    /**
     * Add choice
     *
     * @param AttendanceListColumnChoice $choice
     * @return AttendanceListColumn
     */
    public function addChoice(AttendanceListColumnChoice $choice)
    {
        $choice->setColumn($this);
        $this->choices[] = $choice;

        return $this;
    }

    /**
     * Remove participation
     *
     * @param AttendanceListColumnChoice $choice
     * @return AttendanceListColumn
     */
    public function removeChoice(AttendanceListColumnChoice $choice)
    {
        $this->choices->removeElement($choice);
        return $this;
    }

    /**
     * @param AttendanceListColumnChoice[]|array|Collection $choices
     * @return AttendanceListColumn
     */
    public function setChoices($choices)
    {
        $this->choices = $choices;
        return $this;
    }

    /**
     * @return AttendanceList[]|array|Collection
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Determine if column contains list
     *
     * @param AttendanceList $list
     * @return bool
     */
    public function hasList(AttendanceList $list): bool
    {
        return $this->lists->contains($list);
    }

    /**
     * Add list
     *
     * @param AttendanceList $list
     * @return AttendanceListColumn
     */
    public function addList(AttendanceList $list)
    {
        $this->lists->add($list);
        if (!$list->hasColumn($this)) {
            $list->addColumn($this);
        }
        return $this;
    }

    /**
     * Remove participation
     *
     * @param AttendanceList $list
     * @return AttendanceListColumn
     */
    public function removeList(AttendanceList $list)
    {
        $this->lists->removeElement($list);
        return $this;
    }

    /**
     * @param AttendanceList[]|array|Collection $lists
     * @return AttendanceListColumn
     */
    public function setLists($lists)
    {
        $this->lists = $lists;
        return $this;
    }


}
