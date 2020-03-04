<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\ChangeTracking;

use Doctrine\ORM\EntityRepository;

/**
 * EmployeeRepository
 */
class EntityChangeRepository extends EntityRepository
{
    /**
     * Get amount of stored changes for transmitted entity
     *
     * @param SupportsChangeTrackingInterface $entity
     * @return int
     */
    public function countAllByEntity(SupportsChangeTrackingInterface $entity): int
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select(['COUNT(c)'])
           ->andWhere('c.relatedId = :relatedId')
           ->andWhere('c.relatedClass = :relatedClass');
        
        $qb->setParameter('relatedId', $entity->getId())
           ->setParameter('relatedClass', get_class($entity));
        
        return (int)$qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * Find all changes for transmitted tracked entity
     *
     * @param SupportsChangeTrackingInterface $entity
     * @return array|EntityChange[]
     */
    public function findAllByEntity(SupportsChangeTrackingInterface $entity): array
    {
        return $this->findAllByClassAndId(get_class($entity), $entity->getId());
    }
    
    /**
     * Find all changes by related class name and related entityid
     *
     * @param string $relatedClassName Class name
     * @param int $relatedEntityId     Entity id
     * @return array|EntityChange[]
     */
    public function findAllByClassAndId(string $relatedClassName, int $relatedEntityId): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select(['c', 'u'])
           ->leftJoin('c.responsibleUser', 'u')
           ->andWhere('c.relatedId = :relatedId')
           ->andWhere('c.relatedClass = :relatedClass')
           ->orderBy('c.occurrenceDate', 'DESC');
        
        $qb->setParameter('relatedId', $relatedEntityId)
           ->setParameter('relatedClass', $relatedClassName);
        
        return $qb->getQuery()->execute();
    }
    
    /**
     * Prepare class name for use in route
     *
     * @param string $className
     * @return string
     */
    public static function convertClassNameForRoute(string $className): string
    {
        return str_replace('\\', '.', $className);
    }
    
    /**
     * Exctract class name from route
     *
     * @param string $routeName
     * @return string
     */
    public static function convertRouteToClassName(string $routeName): string
    {
        return str_replace('.', '\\', $routeName);
    }
    
    
}
