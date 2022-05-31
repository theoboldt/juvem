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
use AppBundle\Manager\Geo\CoordinatesAwareInterface;
use AppBundle\Manager\Geo\OpenStreetMapElement;
use AppBundle\Manager\Geo\OpenStreetMapPlace;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="location_description")
 * @ORM\Entity(repositoryClass="LocationDescriptionRepository")
 */
class LocationDescription implements AddressAwareInterface, CoordinatesAwareInterface
{
    
    /**
     * @ORM\Column(type="integer", name="element_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     * @var int
     */
    private $elementId;
    
    /**
     * OSM Id or null if not found
     *
     * @ORM\Column(type="bigint", name="osm_id", nullable=true)
     *
     * @var int|null
     */
    private $osmId = null;
    
    /**
     * OSM type or null if not found
     *
     * @ORM\Column(type="string", name="osm_type", columnDefinition="ENUM('N', 'W', 'R')", nullable=true)
     *
     * @var string|null
     */
    private $osmType = null;
    
    /**
     * @ORM\Column(type="string", length=128, name="address_street_name", nullable=true)
     *
     * @var string|null
     */
    private $addressStreetName = null;
    
    /**
     * @ORM\Column(type="string", length=16, name="address_street_number", nullable=true)
     *
     * @var string|null
     */
    private $addressStreetNumber = null;
    
    /**
     * @ORM\Column(type="string", length=128, name="address_city", nullable=true)
     *
     * @var string|null
     */
    private $addressCity = null;
    
    /**
     * @ORM\Column(type="string", length=16, name="address_zip", nullable=true)
     *
     * @var string|null
     */
    private $addressZip = null;
    
    /**
     * Related country
     *
     * @ORM\Column(type="string", length=128, name="address_country", nullable=true)
     *
     * @var string|null
     */
    private $addressCountry = null;
    
    /**
     * Location latitude if already fetched
     *
     * @ORM\Column(name="latitude", type="decimal", precision=10, scale=8, nullable=true)
     *
     * @var null|float
     */
    private $locationLatitude = null;
    
    /**
     * Location longitude if already fetched
     *
     * @ORM\Column(name="longitude", type="decimal", precision=11, scale=8, nullable=true)
     *
     * @var null|float
     */
    private $locationLongitude = null;
    
    /**
     * Detailed fetched (if not null) OSM details about this place
     *
     * @ORM\Column(type="json_array", length=16777215, name="details", nullable=true)
     *
     * @var array|null
     */
    private $details = [];
    
    /**
     * Create by address
     *
     * @param AddressAwareInterface $address
     * @return LocationDescription
     */
    public static function createByAddress(AddressAwareInterface $address): LocationDescription
    {
        $item = new self();
        $item->setAddress(
            (string)$address->getAddressStreetName(),
            (string)$address->getAddressStreetNumber(),
            $address->getAddressCity(),
            $address->getAddressZip(),
            $address->getAddressCountry()
        );
        return $item;
    }
    
    /**
     * Create object by address and coordinates
     *
     * @param AddressAwareInterface $address
     * @param CoordinatesAwareInterface $coordinates
     * @return LocationDescription
     */
    public static function createByAddressAndCoordinates(
        AddressAwareInterface $address,
        CoordinatesAwareInterface $coordinates
    ): LocationDescription
    {
        $item = self::createByAddress($address);
        $item->setLocation($coordinates->getLocationLatitude(), $coordinates->getLocationLongitude());
        return $item;
    }
    
    /**
     * Create object by address and OSM place
     *
     * @param AddressAwareInterface $address
     * @param OpenStreetMapPlace $element
     * @return LocationDescription
     */
    public static function createForAddressAndOsmPlace(AddressAwareInterface $address, OpenStreetMapPlace $element
    ): LocationDescription
    {
        $item = self::createByAddressAndCoordinates($address, $element);
        if ($element->isFound()) {
            $item->setOsmIdentifier($element->getOsmId(), $element->getOsmType());
        }
        $item->setDetails($element->jsonSerialize());
        return $item;
    }
    
    /**
     * LocationDescription constructor.
     *
     * @param int $osmId
     * @param string $osmType
     */
    public function __construct(int $osmId = null, string $osmType = null)
    {
        if ($osmId && $osmType) {
            $this->setOsmIdentifier($osmId, $osmType);
        }
    }
    
    /**
     * Configure address
     * 
     * Configure address, truncating text snippets which are too long
     *
     * @param string $addressStreetName
     * @param string $addressStreetNumber
     * @param string $addressCity
     * @param string $addressZip
     * @param string $addressCountry
     */
    public function setAddress(
        string $addressStreetName,
        string $addressStreetNumber,
        string $addressCity,
        string $addressZip,
        string $addressCountry = Event::DEFAULT_COUNTRY
    ): void
    {
        $this->addressStreetName   = mb_substr($addressStreetName, 0, 128);
        $this->addressStreetNumber = mb_substr($addressStreetNumber, 0, 16);
        $this->addressCity         = mb_substr($addressCity, 0, 128);
        $this->addressZip          = mb_substr($addressZip, 0, 16);
        $this->addressCountry      = mb_substr($addressCountry, 0, 128);
    }
    
    /**
     * Remove address information
     */
    public function removeAddress(): void
    {
        $this->addressStreetName   = null;
        $this->addressStreetNumber = null;
        $this->addressCity         = null;
        $this->addressZip          = null;
        $this->addressCountry      = null;
    }
    
    /**
     * Store location information
     *
     * @param float $locationLatitude
     * @param float $locationLongitude
     */
    public function setLocation($locationLatitude, $locationLongitude): void
    {
        $this->locationLatitude  = $locationLatitude;
        $this->locationLongitude = $locationLongitude;
    }
    
    /**
     * Remove location information
     */
    public function removeLocation(): void
    {
        $this->locationLatitude  = null;
        $this->locationLongitude = null;
    }
    
    /**
     * Determine if details set
     *
     * @return bool
     */
    public function hasDetails(): bool
    {
        return $this->details !== null;
    }
    
    /**
     * @return array|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }
    
    /**
     * @param array|null $details
     */
    public function setDetails(?array $details): void
    {
        $this->details = $details;
    }
    
    /**
     * Set OSM identifiers
     *
     * @param int $osmId
     * @param string $osmType
     */
    public function setOsmIdentifier(?int $osmId, ?string $osmType)
    {
        $this->osmId   = $osmId;
        $this->osmType = $osmType ? OpenStreetMapElement::validateOsmType($osmType) : null;
    }
    
    /**
     * @return int
     */
    public function getElementId(): int
    {
        return $this->elementId;
    }
    
    /**
     * @return int|null
     */
    public function getOsmId(): ?int
    {
        return $this->osmId;
    }
    
    /**
     * @return string|null
     */
    public function getOsmType(): ?string
    {
        return $this->osmType;
    }
    
    /**
     * Determine if addresstags specified
     *
     * @return bool
     */
    public function hasAddressTags(): bool
    {
        return is_array($this->getAddressTags());
    }
    
    /**
     * Access addresstags if any specified
     *
     * @return array|null
     */
    private function getAddressTags(): ?array
    {
        if ($this->hasDetails()) {
            if (isset($this->details['addresstags']) && count($this->details['addresstags'])) {
                return $this->details['addresstags'];
            }
            foreach ($this->details as $detail) {
                if (isset($detail['addresstags']) && count($detail['addresstags'])) {
                    return $detail['addresstags'];
                }
            }
        }
        return null;
    }
    
    /**
     * @return string|null
     */
    public function getAddressStreetName(): ?string
    {
        return $this->addressStreetName;
    }
    
    /**
     * Get street name of addresstags if any specified
     *
     * @param bool $useFallback If set to true and no specific addresstag value is stored, falls back to global value if set to true
     * @return string|null
     */
    public function getAddressTagsStreetName(bool $useFallback = false): ?string
    {
        $tags = $this->getAddressTags();
        if ($tags && isset($tags['street'])) {
            return $tags['street'];
        }
        if ($useFallback) {
            return $this->getAddressStreetName();
        }
        return null;
    }
    
    /**
     * @return string|null
     */
    public function getAddressStreetNumber(): ?string
    {
        return $this->addressStreetNumber;
    }
    
    /**
     * Get street number of addresstags if any specified
     *
     * @param bool $useFallback If set to true and no specific addresstag value is stored, falls back to global value if set to true
     * @return string|null
     */
    public function getAddressTagsStreetNumber(bool $useFallback = false): ?string
    {
        $tags = $this->getAddressTags();
        if ($tags && isset($tags['housenumber'])) {
            return $tags['housenumber'];
        }
        if ($useFallback) {
            return $this->getAddressStreetNumber();
        }
        return null;
    }
    
    /**
     * @return string|null
     */
    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }
    
    /**
     * Get city name of addresstags if any specified
     *
     * @param bool $useFallback If set to true and no specific addresstag value is stored, falls back to global value if set to true
     * @return string|null
     */
    public function getAddressTagsCity(bool $useFallback = false): ?string
    {
        $tags = $this->getAddressTags();
        if ($tags && isset($tags['city'])) {
            return $tags['city'];
        }
        if ($useFallback) {
            return $this->getAddressCity();
        }
        return null;
    }
    
    /**
     * @return string|null
     */
    public function getAddressZip(): ?string
    {
        return $this->addressZip;
    }
    
    /**
     * Get zip code of addresstags if any specified
     *
     * @param bool $useFallback If set to true and no specific addresstag value is stored, falls back to global value if set to true
     * @return string|null
     */
    public function getAddressTagsZip(bool $useFallback = false): ?string
    {
        $tags = $this->getAddressTags();
        if ($tags && isset($tags['postcode'])) {
            return $tags['postcode'];
        }
        if ($useFallback) {
            return $this->getAddressZip();
        }
        return null;
    }
    
    /**
     * @return string|null
     */
    public function getAddressCountry(): ?string
    {
        return $this->addressCountry;
    }
    
    /**
     * Get country code of addresstags if any specified
     *
     * @param bool $useFallback If set to true and no specific addresstag value is stored, falls back to global value if set to true
     * @return string|null
     */
    public function getAddressTagsCountry(bool $useFallback = false): ?string
    {
        $tags = $this->getAddressTags();
        if ($tags && isset($tags['country'])) {
            return $tags['country'];
        }
        if ($useFallback) {
            return $this->getAddressCountry();
        }
        return null;
    }
    
    /**
     * @return float|null
     */
    public function getLocationLatitude(): ?float
    {
        return $this->locationLatitude;
    }
    
    /**
     * @return float|null
     */
    public function getLocationLongitude(): ?float
    {
        return $this->locationLongitude;
    }
    
    /**
     * @inheritDoc
     */
    public function isLocationProvided(): bool
    {
        return $this->getLocationLatitude() && $this->getLocationLongitude();
    }
    
    /**
     * @inheritDoc
     */
    public function isAddressSpecified(): bool
    {
        return ($this->getAddressZip() && $this->getAddressCity() && $this->getAddressCountry());
    }
}
