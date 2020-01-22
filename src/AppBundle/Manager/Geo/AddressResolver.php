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

use AppBundle\Entity\LocationDescription;
use AppBundle\Entity\LocationDescriptionRepository;

class AddressResolver implements AddressResolverInterface
{
    const REGEX_STREET_NUMBER = '/(?P<street>.*?)(?P<number>\d+.*)/';
    
    /**
     * @var AddressResolverInterface
     */
    private $resolver;
    
    /**
     * Osm Repository
     *
     * @var LocationDescriptionRepository
     */
    private $repository;
    
    /**
     * AddressResolver constructor.
     *
     * @param AddressResolverInterface $resolver
     * @param LocationDescriptionRepository $repository
     */
    public function __construct(AddressResolverInterface $resolver, LocationDescriptionRepository $repository)
    {
        $this->resolver   = $resolver;
        $this->repository = $repository;
    }
    
    /**
     * Extract street from combined street and street number snipped
     *
     * @param string $streetAndNumber Street and number snippet
     * @return string Street name
     */
    public static function extractStreetName(string $streetAndNumber): string
    {
        if (preg_match(self::REGEX_STREET_NUMBER, $streetAndNumber, $result)) {
            $street = trim($result['street'], " \t\n\r\0\x0B;,");
            if (empty($street)) {
                return $streetAndNumber;
            } else {
                return $street;
            }
        }
        return $streetAndNumber;
    }
    
    /**
     * Extract street number from combined street and street number snipped
     *
     * @param string $streetAndNumber Street and number snippet
     * @return string|null Street number if could be extracted
     */
    public static function extractStreetNumber(string $streetAndNumber): ?string
    {
        if (preg_match(self::REGEX_STREET_NUMBER, $streetAndNumber, $result)) {
            $number = trim($result['number'], " \t\n\r\0\x0B;,");
            if (empty($number)) {
                return null;
            } else {
                return $number;
            }
        }
        return null;
    }
    
    /**
     * Provide object providing coordination information for element if found
     *
     * @param AddressAwareInterface $address Address aware interface
     * @return CoordinatesAwareInterface|null Coordinate providing object or null if not found
     */
    public function provideCoordinates(AddressAwareInterface $address): ?CoordinatesAwareInterface
    {
        if (!$address->isAddressSpecified()) {
            return null;
        }
        $location = $this->repository->findOneByAddress($address);
        if (!$location) {
            $osm = $this->resolver->provideCoordinates($address);
            if ($osm instanceof OpenStreetMapPlace) {
                $location = LocationDescription::createForAddressAndOsmPlace($address, $osm);
            } elseif ($osm instanceof CoordinatesAwareInterface) {
                $location = LocationDescription::createByAddressAndCoordinates($address, $osm);
            } else {
                $location = LocationDescription::createByAddress($address);
            }
            $this->repository->persist($location);
        }
        
        return $location;
    }
}