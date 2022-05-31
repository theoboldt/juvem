<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\Geo;

use AppBundle\Entity\Event;
use AppBundle\Manager\Geo\AddressAwareInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Repository for {@see LocationDescription}
 */
class LocationDescriptionRepository extends EntityRepository
{
    
    /**
     * Finds by osm data
     *
     * @param int $osmId
     * @param string $osmType
     * @return LocationDescription|null
     */
    public function findOneByOsmId(int $osmId, string $osmType): ?LocationDescription
    {
        return $this->findOneBy(
            ['osmId' => (int)$osmId, 'osmType' => LocationDescription::validateOsmType($osmType)]
        );
    }
    
    /**
     * Finds by textual address
     *
     * @param string $addressStreetName
     * @param string $addressStreetNumber
     * @param string $addressCity
     * @param string $addressZip
     * @param string $addressCountry
     * @return object|null
     */
    public function findOneByTextualAddress(
        string $addressStreetName,
        string $addressStreetNumber,
        string $addressCity,
        string $addressZip,
        string $addressCountry = Event::DEFAULT_COUNTRY
    ): ?LocationDescription
    {
        return $this->findOneBy(
            [
                'addressStreetName'   => mb_substr($addressStreetName, 0, 128),
                'addressStreetNumber' => mb_substr($addressStreetNumber, 0, 16),
                'addressCity'         => mb_substr($addressCity, 0, 128),
                'addressZip'          => mb_substr($addressZip, 0, 16),
                'addressCountry'      => mb_substr($addressCountry, 0, 128),
            ]
        );
    }
    
    /**
     * Find for {@see AddressAwareInterface} interface if existing
     *
     * @param AddressAwareInterface $address Address item
     * @return LocationDescription|null
     */
    public function findOneByAddress(AddressAwareInterface $address): ?LocationDescription
    {
        return $this->findOneByTextualAddress(
            (string)$address->getAddressStreetName(),
            (string)$address->getAddressStreetNumber(),
            $address->getAddressCity(),
            $address->getAddressZip(),
            $address->getAddressCountry()
        );
    }
    
    /**
     * Persist location element
     *
     * @param LocationDescription $location
     */
    public function persist(LocationDescription $location)
    {
        $em = $this->getEntityManager();
        $em->persist($location);
        $em->flush();
    }
    
}
