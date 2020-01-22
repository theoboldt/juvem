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


class DetailedOpenStreetMapPlace extends OpenStreetMapPlace
{
    
    /**
     * Upgrade a place by detailed data
     *
     * @param OpenStreetMapElement $input
     * @param array $newDetails
     * @return DetailedOpenStreetMapPlace
     */
    public static function upgradePlace(OpenStreetMapElement $input, array $newDetails): DetailedOpenStreetMapPlace
    {
        $oldDetails         = $input->jsonSerialize();
        $oldDetailsFirst    = reset($oldDetails);
        $mergedDetailsFirst = array_merge_recursive($oldDetailsFirst, $newDetails);
        if ($newDetails['address']) {
            $mergedDetailsFirst['address'] = $newDetails['address'];
        }
        $updatedDetails    = $oldDetails;
        $updatedDetails[0] = $mergedDetailsFirst;
        
        return new self($updatedDetails);
    }
    
    
    /**
     * @return float|null
     */
    public function getLocationLatitude(): ?float
    {
        $latitude = parent::getLocationLatitude();
        $data     = $this->getFirst();
        if (!$latitude
            && isset($data['centroid'])
            && isset($data['centroid']['coordinates'])
            && count($data['centroid']['coordinates']) === 2
        ) {
            $latitude = $data['centroid']['coordinates'][1];
        }
        return $latitude;
    }
    
    /**
     * @return float|null
     */
    public function getLocationLongitude(): ?float
    {
        $longitude = parent::getLocationLongitude();
        $data      = $this->getFirst();
        if (!$longitude
            && isset($data['centroid'])
            && isset($data['centroid']['coordinates'])
            && count($data['centroid']['coordinates']) === 2
        ) {
            $longitude = $data['centroid']['coordinates'][0];
        }
        return $longitude;
    }
    
    /**
     * Get addresses elements
     *
     * @return array|OpenStreetMapElement[]
     */
    public function getAddresses(): array
    {
        $addresses = [];
        $data      = $this->getFirst();
        foreach ($data['address'] as $address) {
            $addresses[] = new OpenStreetMapElement([$address]);
        }
        
        return $addresses;
    }
    
    /**
     * Get next administrative element in hierarchy
     *
     * @return OpenStreetMapElement|null
     */
    public function getNextAdministrativeAddress(): ?OpenStreetMapElement
    {
        $addresses = $this->getAddresses();
        /** @var OpenStreetMapElement $address */
        foreach ($addresses as $address) {
            if ($address->getType() === 'administrative') {
                return $address;
            }
        }
        return null;
    }
    
    
}