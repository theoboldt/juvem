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

use AppBundle\Entity\Employee;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\BankAccountType;
use AppBundle\Form\GroupType;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as FormChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="acquisition_attribute_fillout")
 */
class Fillout
{
    /**
     * @ORM\Column(type="integer", name="oid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $oid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AcquisitionAttribute\Attribute", inversedBy="fillouts")
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
     * @ORM\Column(type="string", length=255, name="value", nullable=true)
     * @var string
     */
    protected $value;

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
     * @return Participation
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
     * @return Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Get employee this fillout is related to
     *
     * @return Employee
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
     * @return string
     */
    public function getRawValue() {
        return $this->value;
    }

    /**
     * Get value of this fillout
     *
     * @return FilloutValue
     */
    public function getValue()
    {
        switch ($this->attribute->getFieldType()) {
            case GroupType::class:
                return new GroupFilloutValue($this->attribute, $this->value);
            case FormChoiceType::class:
                return new ChoiceFilloutValue($this->attribute, $this->value);
            case BankAccountType::class:
                return new BankAccountFilloutValue($this->attribute, $this->value);
            case DateType::class:
                return new DateFilloutValue($this->attribute, $this->value);
            default:
                return new FilloutValue($this->attribute, $this->value);
        }
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
     * @param string $choicePresentation Configuration for selected choice option presentation, @see AttributeChoiceOption
     * @return string
     */
    public function getTextualValue(string $choicePresentation = AttributeChoiceOption::PRESENTATION_FORM_TITLE) {
        $value = $this->getValue();
        if ($value instanceof ChoiceFilloutValue) {
            return $value->getTextualValue($choicePresentation);

        } else {
            return $value->getTextualValue();
        }
    }
}
