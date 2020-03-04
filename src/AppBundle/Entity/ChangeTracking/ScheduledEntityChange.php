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


use AppBundle\Entity\User;
use AppBundle\Entity\ChangeTracking\EntityCollectionChange;

class ScheduledEntityChange implements \Countable
{
    /**
     * Related entity
     *
     * @var SupportsChangeTrackingInterface
     */
    private $entity;
    
    /**
     * Related entity id
     *
     * Related entity id; must be read before commit in case of delete events, must be read after commit in case
     * of insert events
     *
     * @var int|null
     */
    private $entityId;
    
    /**
     * Change operation, one of 'create', 'update', 'delete', 'trash', 'restore'
     *
     * @var string
     */
    private $operation;
    
    /**
     * List of attribute changes
     *
     * @var array
     */
    protected $attributeChanges = [];
    
    /**
     * List of collection changes
     *
     * @var array
     */
    protected $collectionChanges = [];
    
    /**
     * If this action was performed by a user, then it is linked here
     *
     * @var User|null
     */
    protected $responsibleUser = null;
    
    /**
     * Timestamp when change happened
     *
     * @var \DateTime
     */
    protected $occurrenceDate;
    
    /**
     * ScheduledEntityChange constructor.
     *
     * @param SupportsChangeTrackingInterface $entity
     * @param string $operation
     * @param User|null $responsibleUser
     * @param null|\DateTime $occurrenceDate
     */
    public function __construct(
        SupportsChangeTrackingInterface $entity, string $operation, ?User $responsibleUser, ?\DateTime $occurrenceDate
    )
    {
        $this->entity = $entity;
        if ($this->entity->getId() !== null) {
            $this->entityId = $this->entity->getId();
        }
        $this->operation       = $operation;
        $this->responsibleUser = $responsibleUser;
        $this->occurrenceDate  = $occurrenceDate ?: new \DateTime();
    }
    
    /**
     * @return SupportsChangeTrackingInterface
     */
    public function getEntity(): SupportsChangeTrackingInterface
    {
        return $this->entity;
    }
    
    /**
     * Determine if a related can be provided
     *
     * @return bool
     */
    public function hasRelatedId(): bool
    {
        return $this->entityId !== null || $this->entity->getId() !== null;
    }
    
    /**
     * @return int
     */
    public function getRelatedId(): int
    {
        if ($this->entityId === null) {
            $this->entityId = $this->entity->getId();
            if ($this->entityId === null) {
                throw new \InvalidArgumentException('An id can not yet be provided');
            }
        }
        return $this->entityId;
    }
    
    /**
     * @return string
     */
    public function getRelatedClass(): string
    {
        return get_class($this->entity);
    }
    
    /**
     * @param string $operation
     */
    public function setOperation(string $operation): void
    {
        $this->operation = $operation;
    }
    
    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
    
    /**
     * Register a change
     *
     * @param string $property              Property to change
     * @param string|int|float|null $before Value before change
     * @param string|int|float|null $after  Value after change
     */
    public function addAttributeChange(string $property, $before, $after): void
    {
        $this->attributeChanges[$property] = [$before, $after];
    }
    
    /**
     * @return array
     */
    public function getAttributeChanges(): array
    {
        return $this->attributeChanges;
    }
    
    /**
     * Register a collection change
     *
     * @param string $property             Property to change
     * @param string $operation            Action code, either {@see EntityCollectionChange::OPERATION_INSERT}
     *                                     or {@see EntityCollectionChange::OPERATION_INSERT}
     * @param object $related              Related entity
     */
    public function scheduleCollectionChange(
        string $property, string $operation, $related
    ): void
    {
        $data = [
            'class' => get_class($related),
            'object' => $related
        ];
        if ($related instanceof SupportsChangeTrackingInterface || method_exists($related, 'getId')) {
            $data['id'] = $related->getId();
        }
        if ($related instanceof SpecifiesChangeTrackingStorableRepresentationInterface) {
            $data['name'] = $related->getChangeTrackingStorableRepresentation();
        }
        $this->collectionChanges[$property][$operation][] = $data;
    }
    
    /**
     * Walk through collection changes and lazy fetch ids if not yet fetched
     *
     * @return array
     */
    public function getCollectionChanges(): array
    {
        $collectionChanges = [];
        foreach ($this->collectionChanges as $property => $operations) {
            foreach ($operations as $operation => $changes) {
                foreach ($changes as $change) {
                    if (!isset($change['id'])
                        && ($change['object'] instanceof SupportsChangeTrackingInterface
                            || method_exists($change['object'], 'getId'))) {
                        $change['id'] = $change['object']->getId();
                        unset($change['name']);
                    }
                    if (empty($change['name'])) {
                        if ($change['object'] instanceof SpecifiesChangeTrackingStorableRepresentationInterface) {
                            $change['name'] = $change['object']->getChangeTrackingStorableRepresentation();
                        } else {
                            $change['name'] = sprintf('%s [%d]', $change['class'], $change['id']);
                        }
                    }
                    $collectionChanges[$property][$operation][] = [
                        'class' => $change['class'],
                        'id'    => $change['id'],
                        'name'  => $change['name'],
                    ];
                }
            
            }
        }
        return $collectionChanges;
    }
    
    /**
     * @return User|null
     */
    public function getResponsibleUser(): ?User
    {
        return $this->responsibleUser;
    }
    
    /**
     * @param \DateTime $occurrenceDate
     */
    public function setOccurrenceDate(\DateTime $occurrenceDate): void
    {
        $this->occurrenceDate = $occurrenceDate;
    }
    
    /**
     * @return \DateTime
     */
    public function getOccurrenceDate(): \DateTime
    {
        return $this->occurrenceDate;
    }
    
    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->attributeChanges);
    }
}