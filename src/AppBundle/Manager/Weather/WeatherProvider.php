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
        $weather = $this->repository->findByCoordinates($item);
        if (!$weather) {
            $info = $this->provider->provideCurrentWeather($item);
            if ($info instanceof CurrentWeather) {
                $this->repository->persist($info);
            }
            return $info;
        }
        return $weather;
    }
}