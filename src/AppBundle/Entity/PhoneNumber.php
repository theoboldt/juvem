<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
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
     * @AssertPhoneNumber
     * @Assert\NotBlank()
     */
    protected $number;

    /**
     * @ORM\Column(type="string", length=255, name="description")
     */
    protected $description = '';

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="phoneNumbers")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade", nullable=true)
     */
    protected $participation = null;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Employee", inversedBy="phoneNumbers")
     * @ORM\JoinColumn(name="gid", referencedColumnName="gid", onDelete="cascade", nullable=true)
     */
    protected $employee = null;

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
     * @param string|\libphonenumber\PhoneNumber $number
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
     * @return string|\libphonenumber\PhoneNumber
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
        if (!$participation->getPhoneNumbers()->contains($this)) {
            $participation->addPhoneNumber($this);
        }

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

    /**
     * Set employee
     *
     * @param Employee $employee
     *
     * @return PhoneNumber
     */
    public function setEmployee(Employee $employee = null)
    {
        $this->employee = $employee;
        $found          = false;
        foreach ($employee->getPhoneNumbers() as $phoneNumber) {
            if ($phoneNumber === $this) {
                $found = true;
            }
        }
        if (!$found) {
            $employee->addPhoneNumber($this);
        }

        return $this;
    }

    /**
     * Get employee
     *
     * @return Employee
     */
    public function getEmployee()
    {
        return $this->employee;
    }
}
