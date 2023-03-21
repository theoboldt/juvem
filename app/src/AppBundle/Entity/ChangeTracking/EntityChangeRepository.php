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

use AppBundle\Entity\EntityHavingPhoneNumbersInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

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
     * Find all changes by related class name and related entityid
     *
     * @param string $relatedClassName Class name
     * @param int $relatedEntityId     Entity id
     * @return array|EntityChange[]
     */
    public function findAllByClassAndId(string $relatedClassName, int $relatedEntityId): array
    {
        $qb = $this->createdDefaultQueryBuilder();
        $qb->andWhere('c.relatedId = :relatedId')
           ->andWhere('c.relatedClass = :relatedClass');
        
        $qb->setParameter('relatedId', $relatedEntityId)
           ->setParameter('relatedClass', $relatedClassName);
        
        return $qb->getQuery()->execute();
    }
    
    /**
     * Find all for specified entity and include related fetches as specified
     *
     * @param SupportsChangeTrackingInterface $entity Entity to fetch
     * @return array|EntityChange[]
     */
    public function findAllByEntity(SupportsChangeTrackingInterface $entity): array
    {
        $qb        = $this->createdDefaultQueryBuilder();
        $parameter = 0;
        if ($entity instanceof EntityHavingFilloutsInterface) {
            /** @var \AppBundle\Entity\AcquisitionAttribute\Fillout $fillout */
            foreach ($entity->getAcquisitionAttributeFillouts() as $fillout) {
                $this->addRelatedFetchToQueryBuilder($qb, $fillout, $parameter);
                $qb->orWhere('c.relatedId = :relatedId AND c.relatedClass = :relatedClass');
            }
        }
        if ($entity instanceof EntityHavingPhoneNumbersInterface) {
            /** @var \AppBundle\Entity\PhoneNumber $number */
            foreach ($entity->getPhoneNumbers() as $number) {
                $this->addRelatedFetchToQueryBuilder($qb, $number, $parameter);
                $qb->orWhere('c.relatedId = :relatedId AND c.relatedClass = :relatedClass');
            }
        }
        
        $qb->orWhere('c.relatedId = :relatedId AND c.relatedClass = :relatedClass');
        $qb->setParameter('relatedId', $entity->getId())
           ->setParameter('relatedClass', get_class($entity));
        
        return $qb->getQuery()->execute();
    }
    
    /**
     * Add related fetch to query builder
     *
     * @param QueryBuilder $qb                        Query builder
     * @param SupportsChangeTrackingInterface $entity Entity to add to fetch
     * @param int $parameterNumber                    Parameter number as reference, will be incremented after adding the parameter
     */
    private function addRelatedFetchToQueryBuilder(
        QueryBuilder $qb, SupportsChangeTrackingInterface $entity, int &$parameterNumber
    ): void
    {
        $parameterId    = 'id_' . $parameterNumber;
        $parameterClass = 'class_' . $parameterNumber;
        
        $qb->setParameter($parameterId, $entity->getId());
        $qb->setParameter($parameterClass, get_class($entity));
        $qb->orWhere('c.relatedId = :' . $parameterId . ' AND c.relatedClass = :' . $parameterClass);
        $parameterNumber++;
    }
    
    /**
     * Provide default query builder
     *
     * @return QueryBuilder
     */
    private function createdDefaultQueryBuilder(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select(['c', 'u'])
           ->leftJoin('c.responsibleUser', 'u')
           ->addOrderBy('c.occurrenceDate', 'DESC')
           ->addOrderBy('c.relatedClass', 'ASC');
        return $qb;
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
