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

class OpenStreetMapPlace extends OpenStreetMapElement implements CoordinatesAwareInterface
{
    /**
     * @return float|null
     */
    public function getLocationLatitude(): ?float
    {
        $data = $this->getFirst();
        return $data['lat'] ?? null;
    }
    
    /**
     * @return float|null
     */
    public function getLocationLongitude(): ?float
    {
        $data = $this->getFirst();
        return $data['lon'] ?? null;
    }
    
    /**
     * @inheritDoc
     */
    public function isLocationProvided(): bool
    {
        return $this->getLocationLatitude() && $this->getLocationLongitude();
    }

    
}