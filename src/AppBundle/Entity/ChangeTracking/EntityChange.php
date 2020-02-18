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
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity_change", indexes={
 *     @ORM\Index(name="index_related", columns={"related_id", "related_class"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ChangeTracking\EntityChangeRepository")
 */
class EntityChange implements \Countable
{
    const OPERATION_CREATE  = 'create';
    const OPERATION_UPDATE  = 'update';
    const OPERATION_DELETE  = 'delete';
    const OPERATION_TRASH   = 'trash';
    const OPERATION_RESTORE = 'restore';
    
    /**
     * @ORM\Column(type="integer", name="cid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    private $id;
    
    /**
     * @ORM\Column(type="integer", name="related_id")
     * @var int
     */
    private $relatedId;
    
    /**
     * @ORM\Column(type="string", name="related_class", length=255)
     * @var string
     */
    private $relatedClass;
    
    /**
     * Change operation, one of 'create', 'update', 'delete', 'trash', 'restore'
     *
     * @ORM\Column(type="string", name="operation", columnDefinition="ENUM('create', 'update', 'delete', 'trash', 'restore')")
     * @var string
     */
    private $operation;
    
    /**
     * List of attribute changes
     *
     * @ORM\Column(type="json_array", length=16777215, name="changes", nullable=false)
     * @var array
     */
    protected $changes = [];
    
    /**
     * If this action was performed by a user, then it is linked here
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL", nullable=true)
     * @var User|null
     */
    protected $responsibleUser = null;
    
    /**
     * Timestamp when change happened
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="occurrence_date")
     */
    protected $occurrenceDate;
    
    /**
     * Create entity from scheduled changes
     *
     * @param ScheduledEntityChange $scheduled
     * @return EntityChange
     */
    public static function createFromScheduledChange(ScheduledEntityChange $scheduled): EntityChange
    {
        return new self(
            $scheduled->getRelatedId(),
            $scheduled->getRelatedClass(),
            $scheduled->getOperation(),
            $scheduled->getResponsibleUser(),
            $scheduled->getOccurrenceDate(),
            $scheduled->getChanges()
        );
    }
    
    /**
     * EntityChange constructor.
     *
     * @param int $relatedId
     * @param string $relatedClass
     * @param string $operation
     * @param User|null $responsibleUser
     * @param \DateTime|null $occurrenceDate
     * @param array $changes
     */
    public function __construct(
        int $relatedId,
        string $relatedClass,
        string $operation,
        ?User $responsibleUser = null,
        ?\DateTime $occurrenceDate = null,
        array $changes = []
    )
    {
        $this->relatedId       = $relatedId;
        $this->relatedClass    = $relatedClass;
        $this->operation       = $operation;
        $this->changes         = $changes;
        $this->responsibleUser = $responsibleUser;
        $this->occurrenceDate  = $occurrenceDate ?: new \DateTime();
    }
    
    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    /**
     * @return int
     */
    public function getRelatedId(): int
    {
        return $this->relatedId;
    }
    
    /**
     * @return string
     */
    public function getRelatedClass(): string
    {
        return $this->relatedClass;
    }
    
    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
    
    /**
     * @return User|null
     */
    public function getResponsibleUser(): ?User
    {
        return $this->responsibleUser;
    }
    
    /**
     * @return \DateTime
     */
    public function getOccurrenceDate(): \DateTime
    {
        return $this->occurrenceDate;
    }
    
    /**
     * Get all changes of attributes (f any)
     *
     * @return \Traversable|EntityAttributeChange[]
     */
    public function getChanges(): \Traversable
    {
        foreach ($this->changes as $attribute => $value) {
            yield new EntityAttributeChange($attribute, $value[0], $value[1]);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->changes);
    }
}