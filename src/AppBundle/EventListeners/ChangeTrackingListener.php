<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventListeners;


use AppBundle\Entity\ChangeTracking\EntityChange;
use AppBundle\Entity\ChangeTracking\ScheduledEntityChange;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingComparableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ChangeTrackingListener
{
    /**
     * Token storage interface
     *
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * List of {@see EntityChange} scheduled to be persisted
     *
     * @var \SplQueue
     */
    private $changes;
    
    /**
     * ChangeTrackingListener constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     */
    public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger       = $logger;
        $this->changes      = new \SplQueue();
        $this->changes->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
    }
    
    /**
     * Update entity
     *
     * @param PreUpdateEventArgs $args Pre update args
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getEntity();
        if (!$entity instanceof SupportsChangeTrackingInterface) {
            return;
        }
        $changes = $args->getEntityChangeSet();
        
        $change = $this->createChangeTrackingEntity($entity, EntityChange::OPERATION_UPDATE);
        if (array_key_exists('deletedAt', $changes)) {
            $change->setOperation(
                ($changes['deletedAt'][1] === null)
                    ? EntityChange::OPERATION_RESTORE : EntityChange::OPERATION_TRASH
            );
        }
        if (array_key_exists('modifiedAt', $changes)) {
            $change->setOccurrenceDate($changes['modifiedAt'][1]);
        }
        
        foreach ($changes as $attribute => $values) {
            if ($attribute === 'deletedAt' || $attribute === 'modifiedAt') {
                //do not treat deletedAt changes as update
                continue;
            }
            $comparableBefore = $this->getComparableRepresentation($attribute, $entity, $values[0]);
            $comparableAfter  = $this->getComparableRepresentation($attribute, $entity, $values[1]);
            
            if (is_float($comparableBefore) || is_float($comparableAfter)) {
                $comparableBefore = ($comparableBefore === null) ?: round($comparableBefore, 10);
                $comparableAfter  = ($comparableAfter === null) ?: round($comparableAfter, 10);
            }
            
            if ($comparableBefore !== $comparableAfter) {
                $change->addChange(
                    $attribute,
                    $this->getStorableRepresentation($attribute, $entity, $values[0]),
                    $this->getStorableRepresentation($attribute, $entity, $values[1])
                );
            }
        }
        if (count($change) || $change->getOperation() !== EntityChange::OPERATION_UPDATE) {
            $this->changes->enqueue($change);
        }
    }
    
    /**
     * On entity remove
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof SupportsChangeTrackingInterface) {
            return;
        }
        $change = $this->createChangeTrackingEntity($entity, EntityChange::OPERATION_DELETE);
        $this->changes->enqueue($change);
    }
    
    /**
     * On entity flush check for entity inserts & deletes
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof SupportsChangeTrackingInterface) {
                $change = $this->createChangeTrackingEntity($entity, EntityChange::OPERATION_CREATE);
                $this->changes->enqueue($change);
            }
        }
        
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof SupportsChangeTrackingInterface) {
                //not actually expected
                $change = $this->createChangeTrackingEntity(
                    $entity,
                    EntityChange::OPERATION_DELETE
                );
                $this->changes->enqueue($change);
            }
        }
    }
    
    /**
     * Persist registered changes
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $persisted = false;
        /** @var ScheduledEntityChange $scheduled */
        foreach ($this->changes as $scheduled) {
            $change    = EntityChange::createFromScheduledChange($scheduled);
            $persisted = true;
            $args->getEntityManager()->persist($change);
        }
        if ($persisted) {
            $args->getEntityManager()->flush();
        }
    }
    
    
    /**
     * Get a comparable representation for value
     *
     * @param string $attribute                       Attribute in case of trouble
     * @param SupportsChangeTrackingInterface $entity Entity in case of trouble
     * @param mixed $value                            Value to convert to comparable
     * @return float|int|string|null|array
     */
    private function getComparableRepresentation(string $attribute, SupportsChangeTrackingInterface $entity, $value)
    {
        if ($value instanceof SpecifiesChangeTrackingComparableRepresentationInterface) {
            return $value->getComparableRepresentation();
        } elseif ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        } elseif (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return $value->__toString();
            } else {
                $this->logger->warning(
                    'No reliable comparable representation for attribute {attribute} of entity {entityClass}:{entityId} {valueClass} available',
                    [
                        'attribute'   => $attribute,
                        'valueClass'  => get_class($value),
                        'entityClass' => get_class($entity),
                        'entityId'    => $entity->getId(),
                    ]
                );
            }
        } elseif (is_array($value)) {
            $this->logger->warning(
                'Possibly incorrect comparison of array values for attribute {attribute} of entity {entityClass}:{entityId}',
                [
                    [
                        'attribute'   => $attribute,
                        'entityClass' => get_class($entity),
                        'entityId'    => $entity->getId(),
                    ]
                ]
            );
            $value = implode(', ', $value);
        }
        
        return $value;
    }
    
    /**
     * Get a textual/scalar representation which can be stored in log
     *
     * @param string $attribute                       Attribute in case of trouble
     * @param SupportsChangeTrackingInterface $entity Entity in case of trouble
     * @param mixed $value                            Value to convert to be stored
     * @return string|int|float|null
     */
    private function getStorableRepresentation(string $attribute, SupportsChangeTrackingInterface $entity, $value)
    {
        if ($value instanceof SpecifiesChangeTrackingStorableRepresentationInterface) {
            return $value->getComparableRepresentation();
        } elseif ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        } elseif (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return $value->__toString();
            } else {
                $this->logger->error(
                    'No storable representation for attribute {attribute} of entity {entityClass}:{entityId} {valueClass} available',
                    [
                        'attribute'   => $attribute,
                        'valueClass'  => get_class($value),
                        'entityClass' => get_class($entity),
                        'entityId'    => $entity->getId(),
                    ]
                );
                return get_class($value);
            }
        } elseif (is_array($value)) {
            $value = implode(', ', $value);
        }
        
        return $value;
    }
    
    /**
     * Create a change tracking entity for transmitted object
     *
     * @param SupportsChangeTrackingInterface $entity
     * @param string $operation
     * @param \DateTime|null $occurrenceDate
     * @return ScheduledEntityChange
     */
    private function createChangeTrackingEntity(
        SupportsChangeTrackingInterface $entity,
        string $operation,
        ?\DateTime $occurrenceDate = null
    ): ScheduledEntityChange
    {
        $token = $this->tokenStorage->getToken();
        $user  = $token ? $token->getUser() : null;
        $user  = $user instanceof User ? $user : null;
        
        return new ScheduledEntityChange($entity, $operation, $user, $occurrenceDate);
    }
}