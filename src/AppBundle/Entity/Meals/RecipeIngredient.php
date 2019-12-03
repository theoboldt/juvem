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
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="recipe_ingredient")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 */
class RecipeIngredient implements SoftDeleteableInterface
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
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\Meals\Recipe", inversedBy="ingredients", cascade={"persist"})
     * @ORM\JoinColumn(name="rid", referencedColumnName="id", onDelete="cascade")
     * @var Recipe
     */
    protected $recipe;
    
    /**
     * @ORM\ManyToOne(targetEntity="Viand", inversedBy="recipientUse", cascade={"persist"})
     * @ORM\JoinColumn(name="iid", referencedColumnName="id", onDelete="cascade")
     * @var Viand
     */
    protected $viand;
    
    /**
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\Meals\QuantityUnit", inversedBy="usesInRecipes")
     * @ORM\JoinColumn(name="uid", referencedColumnName="id", onDelete="restrict")
     * @var QuantityUnit
     */
    protected $unit;
    
    /**
     * @ORM\Column(type="float", name="amount")
     * @var float
     */
    protected $amount = 0;
    
    /**
     * Hints, information, notes
     *
     * @ORM\Column(type="string", length=255, options={"default":""})
     * @var string
     */
    protected $description = '';
    
    /**
     * RecipeIngredient constructor.
     *
     * @param Recipe $recipe
     * @param Viand $viand
     * @param QuantityUnit $unit
     * @param float $amount
     * @param string $description
     */
    public function __construct(
        Recipe $recipe, ?Viand $viand = null, ?QuantityUnit $unit = null, float $amount = 0, string $description = ''
    )
    {
        $this->recipe      = $recipe;
        $this->viand       = $viand;
        $this->unit        = $unit;
        $this->amount      = $amount;
        $this->description = $description;
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
     * @return Recipe
     */
    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }
    
    /**
     * @param Recipe $recipe
     * @return RecipeIngredient
     */
    public function setRecipe(Recipe $recipe): RecipeIngredient
    {
        $this->recipe = $recipe;
        return $this;
    }
    
    /**
     * @return Viand
     */
    public function getViand(): ?Viand
    {
        return $this->viand;
    }
    
    /**
     * @param Viand $viand
     * @return RecipeIngredient
     */
    public function setViand(Viand $viand): RecipeIngredient
    {
        $this->viand = $viand;
        return $this;
    }
    
    /**
     * @return QuantityUnit
     */
    public function getUnit(): ?QuantityUnit
    {
        return $this->unit;
    }
    
    /**
     * @param QuantityUnit $unit
     * @return RecipeIngredient
     */
    public function setUnit(QuantityUnit $unit): RecipeIngredient
    {
        $this->unit = $unit;
        return $this;
    }
    
    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }
    
    /**
     * @param float $amount
     * @return RecipeIngredient
     */
    public function setAmount(float $amount): RecipeIngredient
    {
        $this->amount = $amount;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * @param string $description
     * @return RecipeIngredient
     */
    public function setDescription(string $description): RecipeIngredient
    {
        $this->description = $description;
        return $this;
    }
    
}