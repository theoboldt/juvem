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


class OpenWeatherMapWeatherCondition implements WeatherConditionInterface, \JsonSerializable
{
    
    /**
     * Stores data
     *
     * @var array
     */
    private $data;
    
    /**
     * OpenWeatherMapWeatherCondition constructor
     *
     * @link https://openweathermap.org/weather-conditions
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * Get weather id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->data['id'];
    }
    
    /**
     * Get main weather type descriptor
     *
     * @return string
     */
    public function getMain(): string
    {
        return $this->data['main'];
    }
    
    /**
     * Get weather icon name
     *
     * @return string
     */
    public function getIcon(): string
    {
        return $this->data['icon'];
    }
    
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->data['description'];
    }
    
    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}