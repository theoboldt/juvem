<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Geo;


interface AddressAwareInterface
{
    
    /**
     * Determine if an address is specified
     *
     * @return bool
     */
    public function isAddressSpecified(): bool;
    
    /**
     * Get street name without street number
     *
     * @return string|null
     */
    public function getAddressStreetName(): ?string;
    
    /**
     * Get street number if set
     *
     * @return string|null
     */
    public function getAddressStreetNumber(): ?string;
    
    /**
     * Get city name
     *
     * @return string|null
     * @todo type hint add when using PHP 7.4
     */
    public function getAddressCity();
    
    /**
     * Get ZIP code
     *
     * @return string|null
     * @todo type hint add when using PHP 7.4
     */
    public function getAddressZip();
    
    /**
     * Get country
     *
     * @return string|null
     * @todo type hint add when using PHP 7.4
     */
    public function getAddressCountry();
}