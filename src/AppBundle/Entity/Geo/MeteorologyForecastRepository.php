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
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for {@see WeatherForecast}
 */
class MeteorologyForecastRepository extends EntityRepository
{
    
    /**
     * Persist location element
     *
     * @param WeatherForecast $weatherInfo
     */
    public function persist(WeatherForecast $weatherInfo): void
    {
        $em = $this->getEntityManager();
        $em->persist($weatherInfo);
        $em->flush();
    }
    
    /**
     * Remove location element
     *
     * @param WeatherForecast $weatherInfo
     */
    public function remove(WeatherForecast $weatherInfo): void
    {
        $em = $this->getEntityManager();
        $em->remove($weatherInfo);
        $em->flush();
    }
    
    /**
     * Create query builder for location
     *
     * @param CoordinatesAwareInterface $aware
     * @return QueryBuilder
     */
    private function createQueryBuilderForCoordinates(CoordinatesAwareInterface $aware): QueryBuilder
    {
        $longitude = round($aware->getLocationLongitude(), 2);
        $latitude  = round($aware->getLocationLatitude(), 2);
        
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('w')
           ->from(WeatherForecast::class, 'w')
           ->andWhere($qb->expr()->eq('ROUND(w.locationLongitude, 2)', $longitude))
           ->andWhere($qb->expr()->eq('ROUND(w.locationLatitude, 2)', $latitude));
        return $qb;
    }
    
    /**
     * Find weather by coordinates
     *
     * @param CoordinatesAwareInterface $aware
     * @return WeatherForecast|null
     */
    public function findByCoordinates(CoordinatesAwareInterface $aware): ?WeatherForecast
    {
        $qb = $this->createQueryBuilderForCoordinates($aware);
        
        $result = $qb->getQuery()->getResult();
        return count($result) ? $result[0] : null;
    }
    
    /**
     * Find weather by coordinates being valid at specified time
     *
     * @param CoordinatesAwareInterface $aware
     * @param \DateTimeInterface $begin
     * @param \DateTimeInterface $end
     * @return WeatherForecast|null
     */
    public function findByCoordinatesAndBeginningValidity(
        CoordinatesAwareInterface $aware, \DateTimeInterface $begin, \DateTimeInterface $end
    ): ?WeatherForecast
    {
        $qb = $this->createQueryBuilderForCoordinates($aware);
        $qb->andWhere($qb->expr()->gte('w.validSince', ':validSince'))
           ->setParameter('validSince', $begin->format('Y-m-d h:i:00'));
        $qb->andWhere($qb->expr()->lte('w.validUntil', ':validUntil'))
           ->setParameter('validUntil', $end->format('Y-m-d h:i:59'));
        
        $result = $qb->getQuery()->getResult();
        return count($result) ? $result[0] : null;
    }
    
}
