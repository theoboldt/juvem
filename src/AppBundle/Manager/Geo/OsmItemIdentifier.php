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


class OsmItemIdentifier
{
    
    /**
     * @var int
     */
    private $osmId;
    
    /**
     * @var string
     */
    private $osmType;
    
    
    /**
     * OsmItemIdentifier constructor.
     *
     * @param int $osmId
     * @param string $osmType
     */
    public function __construct(int $osmId, string $osmType)
    {
        $this->osmId   = $osmId;
        $this->osmType = $osmType;
    }
    
    /**
     * @return int|null
     */
    public function getOsmId(): int
    {
        return $this->osmId;
    }
    
    /**
     * @return string|null
     */
    public function getOsmType(): string
    {
        return $this->osmType;
    }
    
}