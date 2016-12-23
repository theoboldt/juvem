<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="flash")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\FlashRepository")
 */
class Flash
{
    /**
     * @ORM\Column(type="integer", name="fid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $fid;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    protected $message;

    /**
     * @ORM\Column(type="string", length=16)
     * @Assert\Choice({"success", "info", "warning", "danger"})
     * @Assert\NotBlank()
     */
    protected $type;

    /**
     * Defines begin of validity of message. Use null for unlimited
     *
     * @ORM\Column(type="datetime", name="valid_from", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $validFrom = null;

    /**
     * Defines end of validity of message. Use null for unlimited
     *
     * @ORM\Column(type="datetime", name="valid_until", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $validUntil = null;

    /**
     * Get fid
     *
     * @return integer
     */
    public function getFid()
    {
        return $this->fid;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return Flash
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Flash
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set validFrom
     *
     * @param \DateTime $validFrom
     *
     * @return Flash
     */
    public function setValidFrom($validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Get validFrom
     *
     * @return \DateTime
     */
    public function getValidFrom()
    {
        return $this->validFrom;
    }

    /**
     * Set validUntil
     *
     * @param \DateTime $validUntil
     *
     * @return Flash
     */
    public function setValidUntil($validUntil)
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /**
     * Get validUntil
     *
     * @return \DateTime
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }
}
