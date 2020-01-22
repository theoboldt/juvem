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


interface CoordinatesAwareInterface
{
    
    /**
     * Get location latitude description
     *
     * @return float|null
     * @todo type hint add when using PHP 7.4
     */
    public function getLocationLatitude();
    
    /**
     * Get location longitude description
     *
     * @return float|null
     * @todo type hint add when using PHP 7.4
     */
    public function getLocationLongitude();
}