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
use AppBundle\Entity\Geo\MeteorologicalForecastInterface;
use AppBundle\Manager\Geo\CoordinatesAwareInterface;

interface WeatherForecastProviderInterface
{
    /**
     * Provide a list of {@see ClimaticInformationInterface} objects providing a weather forecast for transmitted item
     *
     * @param CoordinatesAwareInterface $item Coordinate aware object
     * @param \DateTimeInterface $begin       If forecast for dates after transmitted time is available, it will be added to result
     * @param \DateTimeInterface $end         Forecast valid after specified date range will not be provided in result
     * @return MeteorologicalForecastInterface|null
     */
    public function provideForecastWeather(
        CoordinatesAwareInterface $item, \DateTimeInterface $begin, \DateTimeInterface $end
    ): ?MeteorologicalForecastInterface;
    
}