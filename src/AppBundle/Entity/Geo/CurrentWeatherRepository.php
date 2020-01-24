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

use Doctrine\ORM\EntityRepository;

/**
 * Repository for {@see CurrentWeather}
 */
class CurrentWeatherRepository extends EntityRepository
{
    
    /**
     * Persist location element
     *
     * @param CurrentWeather $weatherInfo
     */
    public function persist(CurrentWeather $weatherInfo)
    {
        $em = $this->getEntityManager();
        $em->persist($weatherInfo);
        $em->flush();
    }
    
}
