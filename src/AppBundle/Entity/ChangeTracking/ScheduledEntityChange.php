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
    protected $changes = [];
    
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
     * @return int
     */
    public function getRelatedId(): int
    {
        return $this->entity->getId();
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
    public function addChange(string $property, $before, $after): void
    {
        $this->changes[$property] = [$before, $after];
    }
    
    /**
     * @return array
     */
    public function getChanges(): array
    {
        return $this->changes;
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
        return count($this->changes);
    }
}