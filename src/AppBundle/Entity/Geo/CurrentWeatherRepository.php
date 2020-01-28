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

use AppBundle\Manager\Geo\CoordinatesAwareInterface;
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
    public function persist(CurrentWeather $weatherInfo): void
    {
        $em = $this->getEntityManager();
        $em->persist($weatherInfo);
        $em->flush();
    }
    
    /**
     * Remove location element
     *
     * @param CurrentWeather $weatherInfo
     */
    public function remove(CurrentWeather $weatherInfo): void
    {
        $em = $this->getEntityManager();
        $em->remove($weatherInfo);
        $em->flush();
    }
    
    
    /**
     * Find weather by coordinates
     *
     * @param CoordinatesAwareInterface $aware
     * @return CurrentWeather|null
     */
    public function findByCoordinates(CoordinatesAwareInterface $aware): ?CurrentWeather
    {
        $longitude = round($aware->getLocationLongitude(), 2);
        $latitude  = round($aware->getLocationLatitude(), 2);
        
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('w')
           ->from(CurrentWeather::class, 'w')
           ->andWhere($qb->expr()->eq('ROUND(w.locationLongitude, 2)', $longitude))
           ->andWhere($qb->expr()->eq('ROUND(w.locationLatitude, 2)', $latitude));
        
        $result = $qb->getQuery()->getResult();
        return count($result) ? $result[0] : null;
    }
    
}
