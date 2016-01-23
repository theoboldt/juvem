<?php
namespace AppBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="phone_number")
 */
class PhoneNumber
{
    /**
     * @ORM\Column(type="integer", name="pnid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $pnid;

    /**
     * @ORM\Column(type="string", length=64, name="number")
     * @Assert\NotBlank()
     */
    protected $number;

    /**
     * @ORM\Column(type="string", length=255, name="description")
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="phoneNumbers")
     * @ORM\JoinColumn(name="paid", referencedColumnName="paid")
     */
    protected $participation;

    /**
     * Get pnid
     *
     * @return integer
     */
    public function getPnid()
    {
        return $this->pnid;
    }

    /**
     * Set number
     *
     * @param string $number
     *
     * @return PhoneNumber
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return PhoneNumber
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set participation
     *
     * @param \AppBundle\Entity\Participation $participation
     *
     * @return PhoneNumber
     */
    public function setParticipation(\AppBundle\Entity\Participation $participation = null)
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
}
