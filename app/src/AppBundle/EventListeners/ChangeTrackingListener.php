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


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\ChangeTracking\EntityChange;
use AppBundle\Entity\ChangeTracking\EntityCollectionChange;
use AppBundle\Entity\ChangeTracking\ScheduledEntityChange;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingAttributeConvertersInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingComparableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Entity\CustomField\CustomFieldValueCollection;
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
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

        $change = $this->getScheduledChangeForEntity($entity);
        if ($change) {
            $isScheduled = true;
        } else {
            $isScheduled = false;
            $change = $this->createChangeTrackingEntity($entity, EntityChange::OPERATION_UPDATE);
        }
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
            if ($attribute === 'deletedAt' || $attribute === 'modifiedAt'
                || in_array($attribute, $entity::getExcludedAttributes())) {
                //do not treat deletedAt changes as update
                continue;
            }
            
            if ($attribute === 'customFieldValues') {
                $this->compareCustomFieldCollectionsAndAddChanges(
                    $entity,
                    $this->getComparableCustomFieldValueCollectionRepresentation($values[0]),
                    $this->getComparableCustomFieldValueCollectionRepresentation($values[1]),
                    $change
                );
                
                continue;
            }
            $comparableBefore = $this->getComparableRepresentation($attribute, $entity, $values[0]);
            $comparableAfter  = $this->getComparableRepresentation($attribute, $entity, $values[1]);

            if (is_float($comparableBefore) || is_float($comparableAfter)) {
                $comparableBefore = ($comparableBefore === null) ?: round($comparableBefore, 10);
                $comparableAfter  = ($comparableAfter === null) ?: round($comparableAfter, 10);
            }
            if (is_string($comparableAfter) && is_int($comparableBefore)
                || is_int($comparableAfter) && is_string($comparableBefore)) {
                $comparableBefore = (string)$comparableBefore;
                $comparableAfter  = (string)$comparableAfter;
            }
            
            if ($comparableBefore !== $comparableAfter) {
                foreach ($values as $key => $value) {
                    if ($entity instanceof SpecifiesChangeTrackingAttributeConvertersInterface) {
                        $converters = $entity->getChangeTrackingAttributeConverters();
                        if (isset($converters[$attribute])) {
                            $values[$key] = call_user_func($converters[$attribute], $values[$key]);
                        }
                    }
                }
                $change->addAttributeChange(
                    $attribute,
                    $this->getStorableRepresentation($attribute, $entity, $values[0]),
                    $this->getStorableRepresentation($attribute, $entity, $values[1])
                );
            }
        }
        if (!$isScheduled && (count($change) || $change->getOperation() !== EntityChange::OPERATION_UPDATE)) {
            $this->changes->enqueue($change);
        }
    }

    /**
     * Provide custom field name
     *
     * @param Attribute[]               $customFields              List of known custom fields
     * @param CustomFieldValueContainer $customFieldValueContainer Custom field value container
     * @return string Name
     */
    private static function provideCustomFieldName(
        array                     $customFields,
        CustomFieldValueContainer $customFieldValueContainer
    ): string {
        $attributeName = 'Feld #' . $customFieldValueContainer->getCustomFieldId();
        foreach ($customFields as $customField) {
            if ($customField->getBid() === $customFieldValueContainer->getCustomFieldId()) {
                $attributeName = $customField->getManagementTitle() . ' [' . $attributeName . ']';
            }
        }
        return $attributeName;
    }

    /**
     * Compare custom fields and register changes if any available
     *
     * @todo  Needs to be improved so choice options etc. can be interpret 
     * @param SupportsChangeTrackingInterface $entity
     * @param CustomFieldValueCollection      $comparableBefore
     * @param CustomFieldValueCollection      $comparableAfter
     * @param ScheduledEntityChange           $change
     * @return void
     */
    private function compareCustomFieldCollectionsAndAddChanges(
        SupportsChangeTrackingInterface $entity,
        CustomFieldValueCollection $comparableBefore,
        CustomFieldValueCollection $comparableAfter,
        ScheduledEntityChange      $change
    ): void {
        if (!$entity instanceof EntityHavingCustomFieldValueInterface) {
            throw new \RuntimeException('Entity must be of class '.EntityHavingCustomFieldValueInterface::class);
        }
        
        $customFields = $entity->getEvent()->getAcquisitionAttributes();
        if ($customFields instanceof Collection) {
            $customFields = $customFields->toArray();
        }
        /** @var CustomFieldValueContainer $customFieldValueContainerAfter */
        foreach ($comparableAfter->getIterator() as $customFieldValueContainerAfter) {
            $attributeName = self::provideCustomFieldName($customFields, $customFieldValueContainerAfter);
            
            $customFieldValueContainerBefore = $comparableBefore->get(
                $customFieldValueContainerAfter->getCustomFieldId()
            );
            if ($customFieldValueContainerBefore) {
                $customFieldValueContainerBeforeRepresentation = $this->getStorableRepresentation(
                    $attributeName, $entity, $customFieldValueContainerBefore
                );
            } else {
                $customFieldValueContainerBeforeRepresentation = '';
            }
            if (!$customFieldValueContainerBefore
                || !$customFieldValueContainerBefore->isEqualTo($customFieldValueContainerAfter)) {
                $change->addAttributeChange(
                    $attributeName,
                    $customFieldValueContainerBeforeRepresentation,
                    $this->getStorableRepresentation($attributeName, $entity, $customFieldValueContainerAfter)
                );
            }
        }
        /** @var CustomFieldValueContainer $customFieldValueContainerBefore */
        foreach ($comparableBefore->getIterator() as $customFieldValueContainerBefore) {
            if (!$comparableAfter->get($customFieldValueContainerBefore->getCustomFieldId())) {
                $attributeName = self::provideCustomFieldName($customFields, $customFieldValueContainerBefore);
                $change->addAttributeChange(
                    $attributeName,
                    $this->getStorableRepresentation($attributeName, $entity, $customFieldValueContainerBefore),
                    ''
                );
            }
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
        unset($entity);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof SupportsChangeTrackingInterface) {
                $change = $this->createChangeTrackingEntity(
                    $entity,
                    EntityChange::OPERATION_DELETE
                );
                $this->changes->enqueue($change);
            }
        }
        unset($entity);

        /** @var PersistentCollection $collection */
        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            $entity = $collection->getOwner();
            if (!$entity instanceof SupportsChangeTrackingInterface) {
                continue;
            }
            $change   = $this->getScheduledChangeForEntity($entity);
            $schedule = false;
            if ($change) {
                $isAlreadyScheduled = true;
            } else {
                $isAlreadyScheduled = false;
                $change             = $this->createChangeTrackingEntity($entity, EntityChange::OPERATION_UPDATE);
            }

            $mapping = $collection->getMapping();
            if (!isset($mapping['fieldName'])) {
                $this->logger->warning(
                    'Collection of owner {ownerClass}:{ownerId} did not provide a fieldName in mapping',
                    [
                        'ownerClass' => get_class($collection->getOwner()),
                        'ownerId'    => $collection->getOwner()->getId(),
                    ]
                );
                continue;
            }
            $property = $mapping['fieldName'];
            if (in_array($property, $entity::getExcludedAttributes())) {
                continue;
            }
            foreach ($collection->getDeleteDiff() as $related) {
                $schedule = true;
                $this->integrateCollectionChange(
                    $change, $property, EntityCollectionChange::OPERATION_DELETE, $related
                );
            }
            foreach ($collection->getInsertDiff() as $related) {
                $schedule = true;
                $this->integrateCollectionChange(
                    $change, $property, EntityCollectionChange::OPERATION_INSERT, $related
                );
            }
            if (!$isAlreadyScheduled && $schedule) {
                $this->changes->enqueue($change);
            }
        }
    }

    /**
     * Track a collection change
     *
     * @param ScheduledEntityChange $change           Change bucket to register collection change at
     * @param string $property                        Collection property name of main entity
     * @param string $operation                       Collection change operation
     * @param mixed $related                          Related collection item
     */
    private function integrateCollectionChange(
        ScheduledEntityChange $change,
        string $property,
        string $operation,
        $related
    )
    {
        $change->scheduleCollectionChange(
            $property,
            $operation,
            $related
        );

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
        while (!$this->changes->isEmpty()) {
            $scheduled = $this->changes->dequeue();
            $change    = EntityChange::createFromScheduledChange($scheduled);
            $persisted = true;
            $args->getEntityManager()->persist($change);
        }
        if ($persisted) {
            $args->getEntityManager()->flush();
        }
    }

    /**
     * Create comparable custom field value collection representation
     *
     * @param null|array|CustomFieldValueCollection $value Value
     * @return CustomFieldValueCollection
     */
    private function getComparableCustomFieldValueCollectionRepresentation(
        $value
    ): CustomFieldValueCollection {
        if ($value === null) {
            $value = [];
        }
        if (is_array($value)) {
            return CustomFieldValueCollection::createFromArray($value);
        } elseif ($value instanceof CustomFieldValueCollection) {
            return $value;
        } else {
            throw new \RuntimeException('Unknown value for custom fields occurred: ' . json_encode($value));
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
                    ],
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
            return $value->getChangeTrackingStorableRepresentation();
        } elseif ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        } elseif ($value instanceof CustomFieldValueContainer) {
            $result = $value->getValue()->getTextualValue();
            if ($value->hasComment()) {
                $result .= "\nAnmerkung: ".$value->getComment();
            }
            return $result;
        } elseif (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return $value->__toString();
            } elseif (method_exists($value, 'getId')) {
                $this->logger->warning(
                    'Used id for tracking of {attribute} of entity {entityClass}:{entityId} {valueClass}',
                    [
                        'attribute'   => $attribute,
                        'valueClass'  => get_class($value),
                        'entityClass' => get_class($entity),
                        'entityId'    => $entity->getId(),
                    ]
                );
                return $value->getId();
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
     * Find the scheduled change object for transmitted entity
     *
     * @param SupportsChangeTrackingInterface $entity Entity to look out for related change item
     * @return ScheduledEntityChange|null Scheduled change item if found
     */
    private function getScheduledChangeForEntity(SupportsChangeTrackingInterface $entity): ?ScheduledEntityChange
    {
        $id    = $entity->getId();
        $class = get_class($entity);
        /** @var ScheduledEntityChange $change */
        foreach ($this->changes as $change) {
            if ($change->getRelatedClass() === $class
                && ($id !== null && $change->hasRelatedId() && $id === $change->getRelatedId())
                && ($entity === $change->getEntity())
            ) {
                return $change;
            }
        }

        return null;
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
