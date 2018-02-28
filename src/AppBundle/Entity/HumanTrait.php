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
use Symfony\Component\Validator\Constraints as Assert;

trait HumanTrait
{

    /**
     * @ORM\Column(type="string", length=128, name="name_first")
     */
    protected $nameFirst;

    /**
     * @ORM\Column(type="string", length=128, name="name_last")
     * @Assert\NotBlank()
     */
    protected $nameLast;

    /**
     * Set nameFirst
     *
     * @param string $nameFirst
     *
     * @return self
     */
    public function setNameFirst($nameFirst)
    {
        $this->nameFirst = $nameFirst;

        return $this;
    }

    /**
     * Get nameFirst
     *
     * @return string
     */
    public function getNameFirst()
    {
        return $this->nameFirst;
    }

    /**
     * Set nameLast
     *
     * @param string $nameLast
     *
     * @return self
     */
    public function setNameLast($nameLast)
    {
        $this->nameLast = $nameLast;

        return $this;
    }

    /**
     * Get nameLast
     *
     * @return string
     */
    public function getNameLast()
    {
        return $this->nameLast;
    }

    /**
     * Get fullname
     *
     * @return string
     */
    public function fullname() {
        return self::generateFullname($this->nameLast, $this->nameFirst);
    }

    /**
     * Generate a full name from name pars
     *
     * @param string $nameLast  Persons last name
     * @param string $nameFirst Persons first name
     * @return string           Full name
     */
    public static function generateFullname($nameLast, $nameFirst = '')
    {
        if ($nameFirst && $nameLast) {
            return $nameFirst . ' ' . $nameLast;
        } elseif ($nameLast) {
            return $nameLast;
        } elseif ($nameFirst) {
            return $nameFirst;
        }
        throw new \InvalidArgumentException('Invalid name combination transmitted');
    }
}