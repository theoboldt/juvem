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


class WeatherDetails implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $details;
    
    /**
     * WeatherDetails constructor.
     *
     * @param array $details
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }
    
    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->details;
    }
}