<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\Meals;


use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\CreatorModifierTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesCreatorInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\ProvidesModifierInterface;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="viand")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 */
class Viand implements HasFoodPropertiesAssignedInterface, SoftDeleteableInterface, ProvidesModifiedInterface, ProvidesCreatedInterface, ProvidesCreatorInterface, ProvidesModifierInterface
{
    use CreatedModifiedTrait, SoftDeleteTrait, CreatorModifierTrait;
    
    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    protected $id;
    
    /**
     * Title
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @var string
     */
    protected $name;
    
    /**
     * @ORM\OneToMany(targetEntity="RecipeIngredient",
     *     mappedBy="viand", cascade={"persist", "remove"})
     * @var Collection|RecipeIngredient[]
     */
    protected $recipientUse;
    
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Meals\QuantityUnit", inversedBy="usedAsDefault")
     * @ORM\JoinColumn(name="default_quantity_unit", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @var QuantityUnit
     */
    protected $defaultUnit = null;
    
    /**
     * @ORM\ManyToMany(targetEntity="FoodProperty")
     * @ORM\JoinTable(name="viand_food_property",
     *      joinColumns={@ORM\JoinColumn(name="viand_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="food_property_id", referencedColumnName="id",
     *      onDelete="CASCADE")})
     * @var Collection|FoodProperty[]
     */
    protected $properties;
    
    /**
     * Viand constructor.
     *
     * @param string $name
     * @param QuantityUnit|null $defaultUnit
     */
    public function __construct(string $name = '', ?QuantityUnit $defaultUnit = null)
    {
        $this->name        = $name;
        $this->defaultUnit = $defaultUnit;
        $this->properties  = new ArrayCollection();
        $this->setCreatedAtNow();
    }
    
    
    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @param string $name
     * @return Viand
     */
    public function setName(string $name): Viand
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * @return RecipeIngredient[]|Collection
     */
    public function getRecipientUse()
    {
        return $this->recipientUse;
    }
    
    /**
     * @param RecipeIngredient[]|Collection $recipientUse
     * @return Viand
     */
    public function setRecipientUse($recipientUse)
    {
        $this->recipientUse = $recipientUse;
        return $this;
    }
    
    /**
     * Determine if default unit is set
     *
     * @return bool
     */
    public function hasDefaultUnit(): bool
    {
        return $this->defaultUnit !== null;
    }
    
    /**
     * @return null|QuantityUnit
     */
    public function getDefaultUnit()
    {
        return $this->defaultUnit;
    }
    
    /**
     * @param QuantityUnit|null $defaultUnit
     * @return Viand
     */
    public function setDefaultUnit(?QuantityUnit $defaultUnit = null)
    {
        $this->defaultUnit = $defaultUnit;
        return $this;
    }
    
    /**
     * Get all properties
     *
     * @return FoodProperty[]|array
     */
    public function getProperties(): array
    {
        return $this->properties->toArray();
    }
    
    /**
     * @param FoodProperty[]|Collection $properties
     * @return Viand
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }
    
}