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

use AppBundle\Entity\Geo\LocationDescription;
use AppBundle\Entity\Geo\LocationDescriptionRepository;

class AddressResolver implements AddressResolverInterface
{
    const REGEX_STREET_NUMBER = '/(?P<street>.*?)(?P<number>\d+.*)/';
    
    const REGEX_NUMBER_STREET = '/(?P<number>\d+)(?:[\s\.]*)(?P<street>.*)/';
    
    /**
     * @var AddressResolverInterface
     */
    private $resolver;
    
    /**
     * Osm Repository
     *
     * @var \AppBundle\Entity\Geo\LocationDescriptionRepository
     */
    private $repository;
    
    /**
     * AddressResolver constructor.
     *
     * @param AddressResolverInterface $resolver
     * @param \AppBundle\Entity\Geo\LocationDescriptionRepository $repository
     */
    public function __construct(AddressResolverInterface $resolver, LocationDescriptionRepository $repository)
    {
        $this->resolver   = $resolver;
        $this->repository = $repository;
    }
    
    /**
     * Extract street name and number using best regex
     *
     * @param string $streetAndNumber
     * @return array|null[]|string[]
     */
    private static function matchStreetAndNumberBest(string $streetAndNumber): array
    {
        $street = null;
        $number = null;
        if (preg_match(self::REGEX_STREET_NUMBER, $streetAndNumber, $result)) {
            $street = self::trims($result['street']);
            $number = self::trims($result['number']);
        }

        if (empty($street) || empty($number)) {
            //try using alternate regex
            if (preg_match(self::REGEX_NUMBER_STREET, $streetAndNumber, $result)) {
                $alternateStreet = self::trims($result['street']);
                $alternateNumber = self::trims($result['number']);

                if (!empty($alternateStreet) && !empty($alternateNumber)) {
                    $street = $alternateStreet;
                    $number = $alternateNumber;
                }
            }
        }
        
        return ['street' => $street, 'number' => $number];
    }
    
    /**
     * Trim according to requirements
     *
     * @param string $content
     * @return string
     */
    private static function trims(string $content): string
    {
        return trim($content, " \t\n\r\0\x0B;,");
    }
    
    /**
     * Extract street from combined street and street number snipped
     *
     * @param string $streetAndNumber Street and number snippet
     * @return string Street name
     */
    public static function extractStreetName(string $streetAndNumber): string
    {
        $result = self::matchStreetAndNumberBest($streetAndNumber);
        return $result['street'] ?? $streetAndNumber;
    }
    
    /**
     * Extract street number from combined street and street number snipped
     *
     * @param string $streetAndNumber Street and number snippet
     * @return string|null Street number if could be extracted
     */
    public static function extractStreetNumber(string $streetAndNumber): ?string
    {
        $result = self::matchStreetAndNumberBest($streetAndNumber);
        return $result['number'] ?? null;
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