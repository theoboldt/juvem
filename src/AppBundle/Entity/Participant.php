<?php
namespace AppBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="participant")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 */
class Participant
{
    use HumanTrait;

    const TYPE_GENDER_FEMALE = 1;
    const TYPE_GENDER_MALE = 2;

    const LABEL_GENDER_FEMALE = 'männlich';
    const LABEL_GENDER_MALE = 'weiblich';

    const TYPE_FOOD_VEGAN = 1;
    const TYPE_FOOD_VEGETARIAN = 2;
    const TYPE_FOOD_NO_PORK = 4;
    const TYPE_FOOD_LACTOSE_FREE = 8;

    const LABEL_FOOD_VEGAN = 'vegan';
    const LABEL_FOOD_VEGETARIAN = 'vegetarisch';
    const LABEL_FOOD_NO_PORK = 'ohne Schweinefleisch';
    const LABEL_FOOD_LACTOSE_FREE = 'laktosefrei';

    const TYPE_STATUS_UNCONFIRMED = 1;
    const TYPE_STATUS_CONFIRMED = 2;
    const TYPE_STATUS_PAID = 4;
    const TYPE_STATUS_WITHDRAWN = 8;

    const LABEL_STATUS_UNCONFIRMED = 'unbestätigt';
    const LABEL_STATUS_CONFIRMED = 'bestätigt';
    const LABEL_STATUS_PAID = 'bezahlt';
    const LABEL_STATUS_WITHDRAWN = 'zurückgezogen';

    /**
     * @ORM\Column(type="integer", name="aid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $aid;

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="participants")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid")
     */
    protected $participation;

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     * @Assert\NotBlank()
     */
    protected $gender;

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    protected $food;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     */
    protected $birthday;

    /**
     * @ORM\Column(type="text", name="info_medical")
     */
    protected $infoMedical = '';

    /**
     * @ORM\Column(type="text", name="info_general")
     */
    protected $infoGeneral = '';

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true}, name="status")
     */
    protected $status = 1;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", name="modified_at")
     */
    protected $modifiedAt;

    /**
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     */
    protected $deletedAt = null;


    /**
     * Constructor
     */
    public function __construct() {
        $this->modifiedAt = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    /**
     * Get aid
     *
     * @return integer
     */
    public function getAid()
    {
        return $this->aid;
    }

    /**
     * Set gender
     *
     * @param integer $gender
     *
     * @return self
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return integer
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set food
     *
     * @param integer $food
     *
     * @return Participant
     */
    public function setFood($food)
    {
        if (is_array($food)) {
            $food   = array_sum($food);
        }

        $this->food = $food;

        return $this;
    }

    /**
     * Get food
     *
     * @return integer
     */
    public function getFood()
    {
        return $this->food;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     *
     * @return Participant
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set infoMedical
     *
     * @param string $infoMedical
     *
     * @return Participant
     */
    public function setInfoMedical($infoMedical)
    {
        $this->infoMedical = $infoMedical;

        return $this;
    }

    /**
     * Get infoMedical
     *
     * @return string
     */
    public function getInfoMedical()
    {
        return $this->infoMedical;
    }

    /**
     * Set infoGeneral
     *
     * @param string $infoGeneral
     *
     * @return Participant
     */
    public function setInfoGeneral($infoGeneral)
    {
        $this->infoGeneral = $infoGeneral;

        return $this;
    }

    /**
     * Get infoGeneral
     *
     * @return string
     */
    public function getInfoGeneral()
    {
        return $this->infoGeneral;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Participant
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     *
     * @return Participant
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Participant
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set participation
     *
     * @param \AppBundle\Entity\Participation $participation
     *
     * @return Participant
     */
    public function setParticipation(Participation $participation = null)
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation
     *
     * @return \AppBundle\Entity\Participation
     */
    public function getParticipation()
    {
        return $this->participation;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Participant
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus($format = false)
    {
        if ($format) {
            $formatLabel = function($text, $type = 'primary') {
                return sprintf('<span class="label label-%s">%s</span>', $type, $text);
            };
            $status = '';
            $status .= ($this->status%self::TYPE_STATUS_UNCONFIRMED == 0) ? $formatLabel(self::LABEL_STATUS_UNCONFIRMED) : '';
            $status .= ($this->status%self::TYPE_STATUS_CONFIRMED == 0) ? $formatLabel(self::LABEL_STATUS_CONFIRMED) : '';
            $status .= ($this->status%self::TYPE_STATUS_PAID == 0) ? $formatLabel(self::LABEL_STATUS_PAID) : '';
            $status .= ($this->status%self::TYPE_STATUS_WITHDRAWN == 0) ? $formatLabel(self::LABEL_STATUS_WITHDRAWN) : '';
            return $status;
        }

        return $this->status;
    }
}
