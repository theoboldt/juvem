<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
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
     * @ORM\Column(type="string", length=255, name="value")
     * @Assert\NotBlank()
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
     * Set attribute
     *
     * @param \AppBundle\Entity\AcquisitionAttribute $attribute
     *
     * @return AcquisitionAttributeFillout
     */
    public function setAttribute(\AppBundle\Entity\AcquisitionAttribute $attribute = null)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get attribute
     *
     * @return \AppBundle\Entity\AcquisitionAttribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set participation
     *
     * @param \AppBundle\Entity\AcquisitionAttribute $participation
     *
     * @return AcquisitionAttributeFillout
     */
    public function setParticipation(\AppBundle\Entity\AcquisitionAttribute $participation = null)
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation
     *
     * @return \AppBundle\Entity\AcquisitionAttribute
     */
    public function getParticipation()
    {
        return $this->participation;
    }

    /**
     * Set participant
     *
     * @param \AppBundle\Entity\Participant $participant
     *
     * @return AcquisitionAttributeFillout
     */
    public function setParticipant(\AppBundle\Entity\Participant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get participant
     *
     * @return \AppBundle\Entity\Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return AcquisitionAttributeFillout
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
