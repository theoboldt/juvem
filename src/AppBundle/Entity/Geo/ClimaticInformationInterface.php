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


interface ClimaticInformationInterface
{
    
    /**
     * Get temperature in °C
     *
     * @return float
     */
    public function getTemperature(): float;
    
    /**
     * Get pressure level
     *
     * @return float|int
     */
    public function getPressure(): int;
    
    /**
     * Get relative humidity level
     *
     * @return int
     */
    public function getRelativeHumidity(): int;
    
    /**
     * Get list of conditions valid for this item
     *
     * @return array|WeatherConditionInterface[]
     */
    public function getWeather(): array;
    
}