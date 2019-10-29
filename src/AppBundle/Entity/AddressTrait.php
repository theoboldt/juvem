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
     * Related country
     *
     * @ORM\Column(type="string", length=128, name="address_country")
     * @Assert\NotBlank()
     */
    protected $addressCountry = Event::DEFAULT_COUNTRY; //configuring default


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
    
    /**
     * @return string
     */
    public function getAddressCountry()
    {
        return $this->addressCountry;
    }
    
    /**
     * @param string $addressCountry
     */
    public function setAddressCountry(string $addressCountry)
    {
        $this->addressCountry = $addressCountry;
    }
    
    /**
     * Get textual address
     *
     * @return string
     */
    public function getAddress(): string
    {
        $address = sprintf(
            '%s, %s %s',
            $this->getAddressStreet(),
            $this->getAddressZip(),
            $this->getAddressCity()
        );
        if ($this->getAddressCountry() !== Event::DEFAULT_COUNTRY) {
            $address .= ' ('.$this->getAddressCountry().')';
        }
        return $address;
    }
}