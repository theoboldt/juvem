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
     * Get feels like temperature in °C
     *
     * @return float
     */
    public function getTemperatureFeelsLike(): float;
    
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
     * Get amount of rain for last 3 hours in mm
     * 
     * @return float
     */
    public function getRainVolume(): float;
    
    /**
     * Get probability of precipitation, 0 for 0%, 1 for 100%
     * 
     * @return float|null
     */
    public function getRainProbability(): ?float;
    
    /**
     * Get list of conditions valid for this item
     *
     * @return array|WeatherConditionInterface[]
     */
    public function getWeather(): array;
    
    /**
     * Provides the timestamp for which this climatic information is valid for
     *
     * @param \DateTimeZone|null $timeZone If transmitted date will be converted to provided time zone
     * @return \DateTimeInterface
     */
    public function getDate(\DateTimeZone $timeZone = null): \DateTimeInterface;
    
}
