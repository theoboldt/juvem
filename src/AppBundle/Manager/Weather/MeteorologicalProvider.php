<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Weather;


use AppBundle\Entity\Geo\ClimaticInformationInterface;
use AppBundle\Entity\Geo\CurrentWeather;
use AppBundle\Entity\Geo\CurrentWeatherRepository;
use AppBundle\Entity\Geo\MeteorologicalForecastInterface;
use AppBundle\Entity\Geo\MeteorologyForecastRepository;
use AppBundle\Entity\Geo\WeatherForecast;
use AppBundle\Manager\Geo\CoordinatesAwareInterface;

class MeteorologicalProvider implements WeatherCurrentProviderInterface, WeatherForecastProviderInterface
{
    /**
     *
     * @var WeatherCurrentProviderInterface
     */
    private $openWeatherMapProvider;
    
    /**
     * Osm Repository
     *
     * @var CurrentWeatherRepository
     */
    private $meteorologyRepositoryCurrent;
    
    /**
     * Osm Repository
     *
     * @var MeteorologyForecastRepository
     */
    private $meteorologyRepositoryForecast;
    
    /**
     * MeteorologicalProvider constructor.
     *
     * @param OpenweathermapMeteorologicalProvider $provider
     * @param CurrentWeatherRepository $meteorologyRepositoryCurrent
     * @param MeteorologyForecastRepository $meteorologyRepositoryForecast
     */
    public function __construct(
        OpenweathermapMeteorologicalProvider $provider,
        CurrentWeatherRepository $meteorologyRepositoryCurrent,
        MeteorologyForecastRepository $meteorologyRepositoryForecast
    )
    {
        $this->openWeatherMapProvider        = $provider;
        $this->meteorologyRepositoryCurrent  = $meteorologyRepositoryCurrent;
        $this->meteorologyRepositoryForecast = $meteorologyRepositoryForecast;
    }
    
    /**
     * @inheritDoc
     */
    public function provideCurrentWeather(CoordinatesAwareInterface $item): ?ClimaticInformationInterface
    {
        if (!$item->isLocationProvided()) {
            return null;
        }
        $weather = $this->meteorologyRepositoryCurrent->findByCoordinates($item);
        if ($weather) {
            $validityLimit = new \DateTime('now', $weather->getCreatedAt()->getTimezone());
            $validityLimit->modify('-1 hour');
            
            if ($weather->getCreatedAt() < $validityLimit) {
                $this->meteorologyRepositoryCurrent->remove($weather);
                $weather = null;
            }
        }
        if (!$weather) {
            $weather = $this->openWeatherMapProvider->provideCurrentWeather($item);
            if ($weather instanceof CurrentWeather) {
                $this->meteorologyRepositoryCurrent->persist($weather);
            }
        }
        return $weather;
    }
    
    /**
     * @inheritDoc
     */
    public function provideForecastWeather(
        CoordinatesAwareInterface $item, \DateTimeInterface $begin, \DateTimeInterface $end
    ): ?MeteorologicalForecastInterface
    {
        if (!$item->isLocationProvided()) {
            return null;
        }
        $forecast = $this->meteorologyRepositoryForecast->findByCoordinatesAndBeginningValidity($item, $begin, $end);
        if ($forecast) {
            return $forecast;
        }
        $forecast = $this->openWeatherMapProvider->provideForecastWeather($item, $begin, $end);
        
        if ($forecast instanceof WeatherForecast) {
            $this->meteorologyRepositoryForecast->persist($forecast);
        }
        
        return $forecast;
    }
}