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
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="attendance_list_column_choices")
 */
class AttendanceListColumnChoice implements SoftDeleteableInterface
{
    use SoftDeleteTrait;

    /**
     * @ORM\Column(type="integer", name="choice_id", options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    protected $choiceId;
    
    /**
     * @ORM\ManyToOne(targetEntity="AttendanceListColumn", inversedBy="choices", cascade={"persist"})
     * @ORM\JoinColumn(name="column_id", referencedColumnName="column_id", onDelete="cascade", nullable=false)
     * @var AttendanceListColumn
     */
    protected $column;
    
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @var string
     */
    protected $title;
    
    /**
     * @ORM\Column(type="string", length=255, name="short_title", nullable=true)
     * @var string|null
     */
    protected $shortTitle = null;
    
    /**
     * AttendanceListColumnChoice constructor.
     *
     * @param string $title
     * @param string|null $shortTitle
     * @param AttendanceListColumn|null $column
     */
    public function __construct(string $title, ?string $shortTitle = null, ?AttendanceListColumn $column = null)
    {
        $this->title      = $title;
        $this->shortTitle = $shortTitle;
        $this->column     = $column;
    }
    
    
    /**
     * @return int|null
     */
    public function getChoiceId(): ?int
    {
        return $this->choiceId;
    }
    
    /**
     * @return AttendanceListColumn
     */
    public function getColumn(): AttendanceListColumn
    {
        return $this->column;
    }
    
    /**
     * @param AttendanceListColumn $column
     * @return AttendanceListColumnChoice
     */
    public function setColumn(AttendanceListColumn $column): AttendanceListColumnChoice
    {
        $this->column = $column;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    
    /**
     * @param string $title
     * @return AttendanceListColumnChoice
     */
    public function setTitle(string $title = null): AttendanceListColumnChoice
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Get shortened title
     *
     * @param bool $fallback If set to true, short title is automatically generated
     * @return string|null
     */
    public function getShortTitle($fallback = false): ?string
    {
        if ($this->shortTitle === null && $fallback) {
            $words = explode(' ', $this->getTitle());
            $title = '';
            foreach ($words as $w) {
                $title .= mb_strtoupper($w[0]);
            }
            return $title;
        }
        
        return $this->shortTitle;
    }
    
    /**
     * @param string|null $shortTitle
     * @return AttendanceListColumnChoice
     */
    public function setShortTitle(?string $shortTitle): AttendanceListColumnChoice
    {
        $this->shortTitle = $shortTitle;
        return $this;
    }
    
}
