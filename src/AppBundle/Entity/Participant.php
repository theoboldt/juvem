<?php
namespace AppBundle\Entity;

use AppBundle\BitMask\ParticipantStatus;
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
    const TYPE_GENDER_MALE   = 2;

    const LABEL_GENDER_FEMALE = 'männlich';
    const LABEL_GENDER_MALE   = 'weiblich';

    const TYPE_FOOD_VEGAN        = 1;
    const TYPE_FOOD_VEGETARIAN   = 2;
    const TYPE_FOOD_NO_PORK      = 4;
    const TYPE_FOOD_LACTOSE_FREE = 8;

    const LABEL_FOOD_VEGAN        = 'vegan';
    const LABEL_FOOD_VEGETARIAN   = 'vegetarisch';
    const LABEL_FOOD_NO_PORK      = 'ohne Schweinefleisch';
    const LABEL_FOOD_LACTOSE_FREE = 'laktosefrei';

    const TYPE_STATUS_UNCONFIRMED = 1;
    const TYPE_STATUS_PAID        = 2;
    const TYPE_STATUS_WITHDRAWN   = 4;

    const LABEL_STATUS_UNCONFIRMED = 'unbestätigt';
    const LABEL_STATUS_CONFIRMED   = 'bestätigt';
    const LABEL_STATUS_PAID        = 'bezahlt';
    const LABEL_STATUS_WITHDRAWN   = 'zurückgezogen';

    /**
     * @ORM\Column(type="integer", name="aid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $aid;

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="participants")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade")
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
    protected $status = 0;

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
    public function __construct()
    {
        $this->modifiedAt = new \DateTime();
        $this->createdAt  = new \DateTime();
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
    public function getGender($formatted = false)
    {
        if ($formatted) {
            switch ($this->gender) {
                case self::TYPE_GENDER_MALE:
                    return self::LABEL_GENDER_MALE;
                case self::TYPE_GENDER_FEMALE:
                    return self::LABEL_GENDER_FEMALE;
            }
        }

        return $this->gender;
    }

    /**
     * Set food
     *
     * @param integer|array $food
     *
     * @return Participant
     */
    public function setFood($food)
    {
        if (is_array($food)) {
            $food = array_sum($food);
        }

        $this->food = $food;

        return $this;
    }

    /**
     * Get food
     *
     * @param   bool    $asArray   Set to true to return food as array
     * @return integer|array
     */
    public function getFood($asArray = true)
    {
        if ($asArray) {
            $result = array();

            $check = function ($type, $label) use ($result) {
                if ($this->food & $type) {
                    $result[] = $label;
                }
            };
            $check(self::TYPE_FOOD_NO_PORK, self::LABEL_FOOD_NO_PORK);
            $check(self::TYPE_FOOD_VEGETARIAN, self::LABEL_FOOD_VEGETARIAN);
            $check(self::TYPE_FOOD_VEGAN, self::LABEL_FOOD_VEGAN);
            $check(self::TYPE_FOOD_LACTOSE_FREE, self::LABEL_FOOD_LACTOSE_FREE);

            sort($result);
            return $result;
        }

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
        if ($infoMedical === null) {
            //due to issue https://github.com/symfony/symfony/issues/5906
            $infoMedical = '';
        }

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
        if ($infoGeneral === null) {
            //due to issue https://github.com/symfony/symfony/issues/5906
            $infoGeneral = '';
        }

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
     * @param integer|ParticipantStatus $status
     *
     * @return Participant
     */
    public function setStatus($status)
    {
        if ($status instanceof ParticipantStatus) {
            $status = $status->getValue();
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @param bool      $asMask             Set to true to get value as mask
     * @return integer|ParticipantStatus
     */
    public function getStatus($asMask = false)
    {
        if ($asMask) {
            return new ParticipantStatus($this->status);
        }

        return $this->status;
    }
}
