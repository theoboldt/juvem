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
use AppBundle\Manager\Geo\CoordinatesAwareInterface;

interface WeatherProviderInterface
{
    /**
     * Provide object providing climatic information for currrent weather
     *
     * @param CoordinatesAwareInterface $item Coordinate aware object
     * @return ClimaticInformationInterface|null Climatic information provider if available
     */
    public function provideCurrentWeather(CoordinatesAwareInterface $item): ?ClimaticInformationInterface;
    
}