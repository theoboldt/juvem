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


trait AddressTrait
{
    /**
     * @ORM\Column(type="string", length=128, name="address_street")
     * @Assert\NotBlank()
     */
    protected $addressStreet;

    /**
     * @ORM\Column(type="string", length=128, name="address_city")
     * @Assert\NotBlank()
     */
    protected $addressCity;

    /**
     * @ORM\Column(type="string", length=16, name="address_zip")
     * @Assert\NotBlank()
     */
    protected $addressZip;


    /**
     * Set addressStreet
     *
     * @param string $addressStreet
     *
     * @return self
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    /**
     * Get addressStreet
     *
     * @return string
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * Set addressCity
     *
     * @param string $addressCity
     *
     * @return self
     */
    public function setAddressCity($addressCity)
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    /**
     * Get addressCity
     *
     * @return string
     */
    public function getAddressCity()
    {
        return $this->addressCity;
    }

    /**
     * Set addressZip
     *
     * @param string $addressZip
     *
     * @return self
     */
    public function setAddressZip($addressZip)
    {
        $this->addressZip = $addressZip;

        return $this;
    }

    /**
     * Get addressZip
     *
     * @return string
     */
    public function getAddressZip()
    {
        return $this->addressZip;
    }
}