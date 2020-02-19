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
use JMS\Serializer\Annotation as Serialize;

/**
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
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
     * @Serialize\Expose
     * @Serialize\Type("string")
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
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @ORM\Column(type="string", name="operation", columnDefinition="ENUM('create', 'update', 'delete', 'trash', 'restore')")
     * @var string
     */
    private $operation;
    
    /**
     * List of attribute changes
     *
     * @ORM\Column(type="json_array", length=16777215, name="attribute_changes", nullable=false)
     * @var array
     */
    protected $attributeChanges = [];
    
    /**
     * List of collection changes
     *
     * @ORM\Column(type="json_array", length=16777215, name="collection_changes", nullable=false)
     * @var array
     */
    protected $collectionChanges = [];
    
    /**
     * If this action was performed by a user, then it is linked here
     *
     * @Serialize\Expose
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL", nullable=true)
     * @var User|null
     */
    protected $responsibleUser = null;
    
    /**
     * Timestamp when change happened
     *
     * @Serialize\Expose
     * @Serialize\Type("DateTime<'d.m.Y H:i'>")
     * @ORM\Column(type="datetime", name="occurrence_date")
     * @var \DateTime
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
            $scheduled->getAttributeChanges(),
            $scheduled->getCollectionChanges()
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
     * @param array $attributeChanges
     * @param array $collectionChanges
     */
    public function __construct(
        int $relatedId,
        string $relatedClass,
        string $operation,
        ?User $responsibleUser = null,
        ?\DateTime $occurrenceDate = null,
        array $attributeChanges = [],
        array $collectionChanges = []
    )
    {
        $this->relatedId         = $relatedId;
        $this->relatedClass      = $relatedClass;
        $this->operation         = $operation;
        $this->attributeChanges  = $attributeChanges;
        $this->collectionChanges = $collectionChanges;
        $this->responsibleUser   = $responsibleUser;
        $this->occurrenceDate    = $occurrenceDate ?: new \DateTime();
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
     * @Serialize\Expose
     * @Serialize\VirtualProperty()
     * @Serialize\SerializedName("attribute_changes")
     * @return array
     */
    public function getAttributeChangesAsArray(): array
    {
        return iterator_to_array($this->getAttributeChanges());
    }
    
    /**
     * Get all changes of attributes (f any)
     *
     * @return \Traversable|EntityAttributeChange[]
     */
    public function getAttributeChanges(): \Traversable
    {
        foreach ($this->attributeChanges as $attribute => $value) {
            yield new EntityAttributeChange($attribute, $value[0], $value[1]);
        }
    }
    
    /**
     * @Serialize\Expose
     * @Serialize\VirtualProperty()
     * @Serialize\SerializedName("collection_changes")
     * @return array
     */
    public function getCollectionChangesAsArray(): array
    {
        return iterator_to_array($this->getCollectionChanges());
    }
    
    /**
     * Get all changes of attributes (f any)
     *
     * @return \Traversable|EntityAttributeChange[]
     */
    public function getCollectionChanges(): \Traversable
    {
        foreach ($this->collectionChanges as $attribute => $operations) {
            foreach ($operations as $operation => $items) {
                foreach ($items as $item) {
                    if (!isset($item['class']) || !isset($item['name'])) {
                        throw new \RuntimeException('Information missing');
                    }
                    yield new EntityCollectionChange(
                        $attribute, $operation, $item['class'], $item['id'] ?? null, $item['name']
                    );
                    
                }
            }
        }
    }
    
    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->attributeChanges);
    }
}