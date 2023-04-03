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


class OpenStreetMapElement implements \JsonSerializable

{
    const OSM_TYPE_NODE     = 'N';
    const OSM_TYPE_WAY      = 'W';
    const OSM_TYPE_RELATION = 'R';
    
    /**
     * Raw OSM data, might contain multiple elements
     *
     * @var array
     */
    private $raw = [];
    
    /**
     * Get first element of list
     *
     * @return array
     */
    protected function getFirst(): array
    {
        if (count($this->raw)) {
            return reset($this->raw);
        } else {
            return [];
        }
    }
    
    /**
     * OpenStreetMapPlace constructor.
     *
     * @param array $raw Raw data
     */
    public function __construct(array $raw)
    {
        $this->raw = $raw;
    }
    
    
    /**
     * Determine if element was found
     *
     * @return bool
     */
    public function isFound(): bool
    {
        return count($this->raw);
    }
    
    /**
     * Determine if multiple matching items were found
     *
     * @return bool
     */
    public function isFoundMultiple(): bool
    {
        return count($this->raw) > 1;
    }
    
    /**
     * @return int|null
     */
    public function getOsmId(): ?int
    {
        $data = $this->getFirst();
        if (isset($data['osm_id'])) {
            if (is_array($data['osm_id'])) {
                $osmId = reset($data['osm_id']);
            } else {
                $osmId = $data['osm_id'];
            }
            return $osmId;
        }
        return null;
    }
    
    /**
     * @return string|null
     */
    public function getOsmType(): ?string
    {
        $data = $this->getFirst();
        if (isset($data['osm_type'])) {
            if (is_array($data['osm_type'])) {
                $osmType = reset($data['osm_type']);
            } else {
                $osmType = $data['osm_type'];
            }
            return self::validateOsmType($osmType);
        }
        return null;
    }
    
    /**
     * Get OSM item type like residential, administrative, suburb, boundary, village, postcode, place
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        $data = $this->getFirst();
        return $data['type'] ?? null;
    }
    
    /**
     * Get OSM place type like suburb, city, locality, municipality, neighourhood, harmlet
     *
     * @link https://wiki.openstreetmap.org/wiki/OSM_Inspector/Views/Places
     * @return string|null
     */
    public function getPlaceType(): ?string
    {
        $data = $this->getFirst();
        return $data['place_type'] ?? null;
    }
    
    /**
     * Get local name of item
     *
     * @return string|null
     */
    public function getLocalName(): ?string
    {
        $data = $this->getFirst();
        return $data['localname'] ?? null;
    }
    
    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
    
    /**
     * Validate osm type and converts uppercase
     *
     * @param string $type Type to validate
     * @return string Validated type if valid
     * @throws \InvalidArgumentException if invalid
     */
    public static function validateOsmType(string $type): string
    {
        $type = strtoupper($type);
        
        switch ($type) {
            case 'WAY':
            case self::OSM_TYPE_WAY:
                return self::OSM_TYPE_WAY;
            case 'NODE':
            case self::OSM_TYPE_NODE:
                return self::OSM_TYPE_NODE;
            case 'RELATION':
            case self::OSM_TYPE_RELATION:
                return self::OSM_TYPE_RELATION;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown type "%s" transmitted', $type));
        }
    }
    
}
