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
     * @param WeatherProviderInterface $resolver
     * @param CurrentWeatherRepository $repository
     */
    public function __construct(WeatherProviderInterface $provider, CurrentWeatherRepository $repository)
    {
        $this->prov       = $provider;
        $this->repository = $repository;
    }
    
    /**
     * @inheritDoc
     */
    public function provideCurrentWeather(CoordinatesAwareInterface $item): ?ClimaticInformationInterface
    {
        // TODO: Implement provideCurrentWeather() method.
    }
}