<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as FormChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="acquisition_attribute_fillout")
 */
class AcquisitionAttributeFillout
{
    /**
     * @ORM\Column(type="integer", name="oid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $oid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AcquisitionAttribute", inversedBy="fillouts")
     * @ORM\JoinColumn(name="bid", referencedColumnName="bid", onDelete="cascade")
     */
    protected $attribute;

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="acquisitionAttributeFillouts")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade", nullable=true)
     */
    protected $participation;

    /**
     * @ORM\ManyToOne(targetEntity="Participant", inversedBy="acquisitionAttributeFillouts")
     * @ORM\JoinColumn(name="aid", referencedColumnName="aid", onDelete="cascade", nullable=true)
     */
    protected $participant;

    /**
     * @ORM\Column(type="string", length=255, name="value", nullable=true)
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
     * @param AcquisitionAttribute $attribute
     *
     * @return AcquisitionAttributeFillout
     */
    public function setAttribute(AcquisitionAttribute $attribute = null)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get attribute this fillout is related to
     *
     * @return AcquisitionAttribute
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
     * @return AcquisitionAttributeFillout
     */
    public function setParticipation(Participation $participation = null)
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation this fillout is related to
     *
     * @return AcquisitionAttribute
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
     * @return AcquisitionAttributeFillout
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
     * @return AcquisitionAttributeFillout
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
                $value = json_decode($value);
            } else {
                $value = [];
            }
        }

        return $value;
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
        $value = $this->getValue();
        if ($value === null) {
            return '';
        }
        $attribute = $this->getAttribute();
        if ($attribute->getFieldType() == FormChoiceType::class) {
            $options = array_flip($attribute->getFieldTypeChoiceOptions(true));
            if ($attribute->getFieldTypeChoiceType()) {
                foreach ($value as &$option) {
                    $option = $options[$option];
                }
                return implode(', ', $value);
            } else {
                return (string)$options[$value];
            }
        }
        return (string)$value;
    }
}
