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

use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as FormChoiceType;
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
     * Set value of this fillout
     *
     * @param string|array $value
     *
     * @return Fillout
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->value = $value;

        return $this;
    }

    /**
     * Get value of this fillout
     *
     * @return string|array
     */
    public function getValue()
    {
        $value     = $this->value;
        $attribute = $this->getAttribute();
        if ($attribute->getFieldTypeChoiceType()) {
            if ($value) {
                $value = json_decode($value, true);
            } else {
                $value = [];
            }
        }

        return $value;
    }

    /**
     * Get list of @see AttributeChoiceOption which are selected
     *
     * @return array|AttributeChoiceOption[]
     */
    public function getSelectedChoices(): array
    {
        if (!$this->attribute->getFieldType() == FormChoiceType::class) {
            throw new \InvalidArgumentException('This is not a fillout related field type');
        }
        $choicesAvailable = $this->attribute->getChoiceOptions();
        $choicesSelected  = $this->getValue();
        $selected         = [];
        if (!is_array($choicesSelected)) {
            $choicesSelected = [$choicesSelected];
        }

        /** @var AttributeChoiceOption $choice */
        foreach ($choicesAvailable as $choice) {
            if (in_array($choice->getId(), $choicesSelected)) {
                $selected[$choice->getId()] = $choice;
            }
        }
        return $selected;
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
        return $this->getTextualValue();
    }

    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @param string $choicePresentation Configuration for selected choice option presentation, @see AttributeChoiceOption
     * @return string
     */
    public function getTextualValue(string $choicePresentation = AttributeChoiceOption::PRESENTATION_FORM_TITLE) {
        $value = $this->getValue();
        if ($value === null) {
            return '';
        }
        $attribute = $this->getAttribute();
        switch ($attribute->getFieldType()) {
            case FormChoiceType::class:
                $choices = $this->getSelectedChoices();
                $values  = [];
                foreach ($choices as $choice) {
                    switch ($choicePresentation) {
                        case AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE:
                            $values[] = $choice->getManagementTitle(true);
                            break;
                        case AttributeChoiceOption::PRESENTATION_SHORT_TITLE:
                            $values[] = $choice->getShortTitle(true);
                            break;
                        default:
                        case AttributeChoiceOption::PRESENTATION_FORM_TITLE:
                            $values[] = $choice->getFormTitle();
                            break;
                    }
                }
                return implode(', ', $values);

            default:
                return (string)$value;
        }
    }
}
