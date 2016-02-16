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
     * @ORM\Column(type="integer", name="nid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $nid;

    /**
     * @ORM\Column(type="phone_number", name="number")
     * @Assert\NotBlank()
     */
    protected $number;

    /**
     * @ORM\Column(type="string", length=255, name="description")
     */
    protected $description = '';

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="phoneNumbers")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade")
     */
    protected $participation;

    /**
     * Get number id
     *
     * @return integer
     */
    public function getNid()
    {
        return $this->nid;
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
        if ($description === null) {
            //due to issue https://github.com/symfony/symfony/issues/5906
            $description = '';
        }
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
     * @param Participation $participation
     *
     * @return PhoneNumber
     */
    public function setParticipation(Participation $participation = null)
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation
     *
     * @return Participation
     */
    public function getParticipation()
    {
        return $this->participation;
    }
}
