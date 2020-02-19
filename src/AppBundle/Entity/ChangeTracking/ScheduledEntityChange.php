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
        $this->entity          = $entity;
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
        return $this->entity->getId() !== null;
    }
    
    /**
     * @return int
     */
    public function getRelatedId(): int
    {
        $id = $this->entity->getId();
        if ($id === null) {
            throw new \InvalidArgumentException('An id can not yet be provided');
        }
        return $id;
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
     * @param string $relatedClassName     Class name of related object
     * @param null|int $relatedId          Id of related entity
     * @param string|int|float|null $value Textual entity identifier
     */
    public function addCollectionChange(
        string $property, string $operation, string $relatedClassName, ?int $relatedId, $value
    ): void
    {
        $this->collectionChanges[$property][$operation][] = [
            'class' => $relatedClassName, 'id' => $relatedId, 'name' => $value
        ];
    }
    
    /**
     * @return array
     */
    public function getCollectionChanges(): array
    {
        return $this->collectionChanges;
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