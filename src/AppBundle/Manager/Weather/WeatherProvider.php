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
use AppBundle\Manager\Geo\CoordinatesAwareInterface;

class WeatherProvider implements WeatherProviderInterface
{
    /**
     *
     * @var WeatherProviderInterface
     */
    private $provider;
    
    /**
     * Osm Repository
     *
     * @var CurrentWeatherRepository
     */
    private $repository;
    
    /**
     * AddressResolver constructor.
     *
     * @param WeatherProviderInterface $provider
     * @param CurrentWeatherRepository $repository
     */
    public function __construct(WeatherProviderInterface $provider, CurrentWeatherRepository $repository)
    {
        $this->provider   = $provider;
        $this->repository = $repository;
    }
    
    /**
     * @inheritDoc
     */
    public function provideCurrentWeather(CoordinatesAwareInterface $item): ?ClimaticInformationInterface
    {
        if (!$item->isLocationProvided()) {
            return null;
        }
        $weather = $this->repository->findByCoordinates($item);
        if ($weather) {
            $validityLimit = new \DateTime('now', $weather->getCreatedAt()->getTimezone());
            $validityLimit->modify('-1 hour');
            
            if ($weather->getCreatedAt() < $validityLimit) {
                $this->repository->remove($weather);
                $weather = null;
            }
        }
        if (!$weather) {
            $weather = $this->provider->provideCurrentWeather($item);
            if ($weather instanceof CurrentWeather) {
                $this->repository->persist($weather);
            }
        }
        return $weather;
    }
}