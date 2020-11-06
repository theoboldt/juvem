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
 * @ORM\Table(name="recipe")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Meals\RecipeRepository")
 */
class Recipe implements HasFoodPropertiesAssignedInterface, SoftDeleteableInterface, ProvidesModifiedInterface, ProvidesCreatedInterface, ProvidesCreatorInterface, ProvidesModifierInterface
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
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @var string
     */
    protected $title;
    
    /**
     * @ORM\Column(type="text", name="cooking_instructions", options={"default":""})
     * @var string
     */
    protected $cookingInstructions = '';
    
    /**
     * @ORM\OneToMany(targetEntity="RecipeIngredient", mappedBy="recipe", cascade={"persist", "remove"})
     * @var Collection|RecipeIngredient[]
     */
    protected $ingredients;
    
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Meals\RecipeFeedback", mappedBy="recipe", cascade={"persist", "remove"})
     * @var Collection|RecipeIngredient[]
     */
    protected $feedbacks;
    
    /**
     * Recipe constructor.
     *
     * @param string $title
     * @param string $cookingInstructions
     */
    public function __construct(string $title, string $cookingInstructions = '')
    {
        $this->title               = $title;
        $this->cookingInstructions = $cookingInstructions;
        $this->ingredients         = new ArrayCollection();
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
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    /**
     * @param string $title
     * @return Recipe
     */
    public function setTitle(string $title): Recipe
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getCookingInstructions(): string
    {
        return $this->cookingInstructions;
    }
    
    /**
     * @param string $cookingInstructions
     * @return Recipe
     */
    public function setCookingInstructions(string $cookingInstructions): Recipe
    {
        $this->cookingInstructions = $cookingInstructions;
        return $this;
    }
    
    
    /**
     * Determine if ingredient is assigned
     *
     * @param int $ingredientId
     * @return bool
     */
    public function hasIngredient(int $ingredientId): bool
    {
        foreach ($this->ingredients as $ingredient) {
            if ($ingredient->getId() === $ingredientId) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Find ingredient by id
     *
     * @param int $ingredientId id of ingredient
     * @return RecipeIngredient
     */
    public function getIngredient(int $ingredientId): RecipeIngredient
    {
        foreach ($this->ingredients as $ingredient) {
            if ($ingredient->getId() === $ingredientId) {
                return $ingredient;
            }
        }
        
        throw new \InvalidArgumentException('Ingredient not found');
    }
    
    /**
     * @return RecipeIngredient[]|array
     */
    public function getIngredients()
    {
        $ingredients = $this->ingredients->toArray();
        usort(
            $ingredients, function (RecipeIngredient $a, RecipeIngredient $b) {
            $aViand = $a->getViand()->getName();
            $bViand = $b->getViand()->getName();
            if ($aViand === $bViand) {
                $aUnit = $a->getUnit()->getName();
                $bUnit = $b->getUnit()->getName();
                if ($aUnit === $bUnit) {
                    $aAmount = $a->getAmount();
                    $bAmount = $b->getAmount();
                    if ($aAmount === $bAmount) {
                        return 0;
                    }
                    return ($aAmount < $bAmount) ? -1 : 1;
                }
                return strnatcasecmp($aUnit, $bUnit);
            }
            return strnatcasecmp($aViand, $bViand);
        }
        );
        return $ingredients;
    }
    
    /**
     * @param RecipeIngredient[]|Collection $ingredients
     * @return Recipe
     */
    public function setIngredients($ingredients)
    {
        $this->ingredients = $ingredients;
        return $this;
    }
    
    /**
     * Get Food properties
     *
     * @return FoodProperty[]|array
     */
    public function getProperties(): array
    {
        $properties = [];
        foreach ($this->getIngredients() as $ingredient) {
            foreach ($ingredient->getViand()->getProperties() as $property) {
                $properties[$property->getId()] = $property;
            }
            
        }
        
        return array_values($properties);
    }
}