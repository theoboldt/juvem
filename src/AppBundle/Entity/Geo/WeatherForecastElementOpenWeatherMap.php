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


class WeatherForecastElementOpenWeatherMap implements \JsonSerializable, ClimaticInformationInterface
{
    /**
     * This forecast detail
     *
     * @var array
     */
    private $details = [];
    
    /**
     * WeatherForecastElementOpenWeatherMap constructor.
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
    
    /**
     * @inheritDoc
     */
    public function getTemperature(): float
    {
        return (float)$this->details['main']['temp'];
    }
    
    /**
     * @inheritDoc
     */
    public function getTemperatureFeelsLike(): float
    {
        return (float)$this->details['main']['feels_like'];
    }
    
    /**
     * @inheritDoc
     */
    public function getPressure(): int
    {
        return (int)$this->details['main']['pressure'];
    }
    
    /**
     * @inheritDoc
     */
    public function getRelativeHumidity(): int
    {
        return (int)$this->details['main']['humidity'];
    }
    
    /**
     * Get amount of rain for last 3 hours in mm
     * 
     * @return float
     */
    public function getRainVolume(): float
    {
        return (float)($this->details['rain']['3h'] ?? 0);
    }
    
    /**
     * @inheritDoc
     */
    public function getWeather(): array
    {
        $result = [];
        foreach ($this->details['weather'] as $weather) {
            $result[] = new OpenWeatherMapWeatherCondition($weather);
        }
        return $result;
    }
    
    /**
     * @inheritDoc
     */
    public function getDate(\DateTimeZone $timeZone = null): \DateTimeInterface
    {
        $date = new \DateTime('@' . (int)$this->details['dt'], new \DateTimeZone('UTC'));
        if ($timeZone) {
            $date->setTimezone($timeZone);
        }
        return $date;
    }
}
