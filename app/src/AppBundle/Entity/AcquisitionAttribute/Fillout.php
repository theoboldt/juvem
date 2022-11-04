<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\AcquisitionAttribute;

use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingAttributeConvertersInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingComparableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\BankAccountType;
use AppBundle\Form\GroupType;
use AppBundle\Form\ParticipantDetectingType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as FormChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

/**
 * @ORM\Entity
 * @ORM\Table(name="acquisition_attribute_fillout")
 */
class Fillout implements SupportsChangeTrackingInterface, SpecifiesChangeTrackingStorableRepresentationInterface, SpecifiesChangeTrackingComparableRepresentationInterface, SpecifiesChangeTrackingAttributeConvertersInterface
{
    /**
     * @ORM\Column(type="integer", name="oid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $oid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AcquisitionAttribute\Attribute", inversedBy="fillouts", fetch="EAGER")
     * @ORM\JoinColumn(name="bid", referencedColumnName="bid", onDelete="cascade")
     * @var Attribute
     */
    protected $attribute;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Participation", inversedBy="acquisitionAttributeFillouts")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade", nullable=true)
     * @var Participation
     */
    protected $participation;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Participant", inversedBy="acquisitionAttributeFillouts")
     * @ORM\JoinColumn(name="aid", referencedColumnName="aid", onDelete="cascade", nullable=true)
     * @var Participant
     */
    protected $participant;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Employee", inversedBy="acquisitionAttributeFillouts")
     * @ORM\JoinColumn(name="gid", referencedColumnName="gid", onDelete="cascade", nullable=true)
     * @var Employee
     */
    protected $employee;

    /**
     * @ORM\Column(type="string", length=2048, name="value", nullable=true)
     * @var string
     */
    protected $value;

    /**
     * @ORM\Column(type="string", length=2048, name="comment", nullable=true)
     * @var string|null
     */
    protected ?string $comment = null;

    /**
     * Get oid
     *
     * @return integer
     */
    public function getOid()
    {
        return $this->oid;
    }

    /**
     * Set attribute this fillout is related to
     *
     * @param Attribute $attribute
     *
     * @return Fillout
     */
    public function setAttribute(Attribute $attribute = null)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get attribute this fillout is related to
     *
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Get bid of the related attribute
     *
     * @return int
     */
    public function getBid()
    {
        return $this->getAttribute()->getBid();
    }

    /**
     * Set participation this fillout is related to
     *
     * Set participation this fillout is related to. Normally a fillout is only related to either a
     * participation or an participant, not both
     *
     * @param Participation $participation
     *
     * @return Fillout
     */
    public function setParticipation(Participation $participation = null)
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation this fillout is related to
     *
     * @return Participation|null
     */
    public function getParticipation()
    {
        return $this->participation;
    }

    /**
     * Set participant this fillout is related to
     *
     * Set participant this fillout is related to. Normally a fillout is only related to either a
     * participation or an participant, not both
     *
     *
     * @param Participant $participant
     *
     * @return Fillout
     */
    public function setParticipant(Participant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get participant this fillout is related to
     *
     * @return Participant|null
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Get employee this fillout is related to
     *
     * @return Employee|null
     */
    public function getEmployee()
    {
        return $this->employee;
    }

    /**
     * Set employee this fillout is related to
     *
     * Set employee this fillout is related to. Normally a fillout is only related to either a
     * employee or an employee, not both
     *
     *
     * @param Employee $employee
     *
     * @return Fillout
     */
    public function setEmployee(Employee $employee = null)
    {
        $this->employee = $employee;

        return $this;
    }

    /**
     * Get event related to this @see Fillout
     *
     * @return Event
     */
    public function getEvent(): Event
    {
        if ($this->getParticipation()) {
            return $this->getParticipation()->getEvent();
        }
        if ($this->getParticipant()) {
            return $this->getParticipant()->getEvent();
        }
        if ($this->getEmployee()) {
            return $this->getEmployee()->getEvent();
        }
        throw new \RuntimeException('No event related');
    }


    /**
     * Set value of this fillout
     *
     * @param string|array $value
     *
     * @return Fillout
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $item = null;
            foreach ($value as &$item) {
                if (is_int($item)) {
                    $item = (string)$item;
                }
            }
            unset($item);
            $value = json_encode($value);
        } elseif ($value instanceof \DateTimeInterface) {
            if ($this->attribute->getFieldType() === DateType::class) {
                $format = 'Y-m-d';
            } else {
                $format = 'Y-m-d h:i:s';
            }
            $value = $value->format($format);
        }
        $this->value = $value;

        return $this;
    }

    /**
     *
     * Get textual raw value
     *
     * @return string
     */
    public function getRawValue()
    {
        return $this->value;
    }

    /**
     * Get value of this fillout
     *
     * @return FilloutValue
     */
    public function getValue()
    {
        return self::convertRawValueForField($this->attribute, $this->value);
    }
    
    /**
     * Get list of @see AttributeChoiceOption which are selected
     *
     * @deprecated
     * @return array|AttributeChoiceOption[]
     */
    public function getSelectedChoices(): array
    {
        if (!$this->attribute->getFieldType() == FormChoiceType::class) {
            throw new \InvalidArgumentException('This is not a fillout related field type');
        }

        /** @var ChoiceFilloutValue $value */
        $value = $this->getValue();
        return $value->getSelectedChoices();
    }

    /**
     * Determine if comment is set
     * 
     * @return bool
     */
    public function hasComment(): bool
    {
        return $this->comment !== null;
    }

    /**
     * Get comment
     * 
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Configure comment for this fillout
     *
     * @param string|null $comment
     * @return Fillout
     */
    public function setComment(?string $comment = null): Fillout
    {
        $comment = trim($comment);
        if (empty($comment)) {
            $comment = null;
        }
        $this->comment = $comment;

        return $this;
    }

    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue()->getTextualValue();
    }

    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @deprecated
     * @param string $choicePresentation Configuration for selected choice option presentation, @see
     *                                   AttributeChoiceOption
     * @return string
     */
    public function getTextualValue(string $choicePresentation = AttributeChoiceOption::PRESENTATION_FORM_TITLE)
    {
        $value = $this->getValue();
        if ($value instanceof ChoiceFilloutValue) {
            return $value->getTextualValue($choicePresentation);

        } else {
            return $value->getTextualValue();
        }
    }
    
    /**
     * Convert transmitted raw value for transmitted @see Attribute to @see FilloutValue
     *
     * @param Attribute $attribute  Related attribute
     * @param string|null $rawValue Raw value from db
     * @return FilloutValue
     */
    public static function convertRawValueForField(Attribute $attribute, $rawValue): FilloutValue
    {
        switch ($attribute->getFieldType()) {
            case GroupType::class:
                return new GroupFilloutValue($attribute, $rawValue);
            case FormChoiceType::class:
                return new ChoiceFilloutValue($attribute, $rawValue);
            case BankAccountType::class:
                return new BankAccountFilloutValue($attribute, $rawValue);
            case DateType::class:
                return new DateFilloutValue($attribute, $rawValue);
            case ParticipantDetectingType::class:
                return new ParticipantFilloutValue($attribute, $rawValue);
            default:
                return new FilloutValue($attribute, $rawValue);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function getComparableRepresentation()
    {
        return $this->getOid();
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingStorableRepresentation()
    {
        if ($this->participation) {
            $related = 'P' . $this->participation->getPid();
        } elseif ($this->participant) {
            $related = 'A' . $this->participant->getAid();
        } elseif ($this->employee) {
            $related = 'G' . $this->employee->getGid();
        } else {
            $related = '?';
        }
        return sprintf(
            'Fillout for %s @ %s [%d]', $this->getAttribute()->getManagementTitle(), $related, $this->getOid()
        );
    }
    
    /**
     * @inheritDoc
     */
    public function getId(): ?int
    {
        return $this->getOid();
    }
    
    /**
     * @inheritDoc
     */
    public static function getExcludedAttributes(): array
    {
        return [];
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingAttributeConverters(): array
    {
        return [
            'value' => function ($value) {
                $formatted = self::convertRawValueForField($this->attribute, $value);
                if ($value) {
                    return $formatted->getTextualValue();
                } else {
                    return $value;
                }
            }
        ];
    }
}
