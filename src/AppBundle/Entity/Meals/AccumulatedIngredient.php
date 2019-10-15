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


class AccumulatedIngredient
{
    
    /**
     * @see Viand
     */
    private $viand;
    
    /**
     * occasions
     *
     * @var array|AccumulatedIngredientOccasion[]
     */
    private $occasions = [];
    
    /**
     * Create new for ingredient
     *
     * @param RecipeIngredient $ingredient
     * @return AccumulatedIngredient
     */
    public static function createForRecipeIngredient(RecipeIngredient $ingredient)
    {
        $e = new self($ingredient->getViand());
        $e->addRecipeIngredient($ingredient);
        return $e;
    }
    
    /**
     * AccumulatedIngredient constructor.
     *
     * @param Viand $viand
     */
    public function __construct(Viand $viand)
    {
        $this->viand = $viand;
    }
    
    /**
     * Add recipe ingredient
     *
     * @param RecipeIngredient $ingredient
     * @return void
     */
    public function addRecipeIngredient(RecipeIngredient $ingredient): void
    {
        foreach ($this->occasions as $occasion) {
            if ($occasion->getUnit()->getId() === $ingredient->getUnit()->getId()) {
                $occasion->addAmount($ingredient->getAmount());
                return;
            }
        }
        $this->occasions[] = new AccumulatedIngredientOccasion($ingredient->getUnit(), $ingredient->getAmount());
    }
    
    /**
     * @return AccumulatedIngredientOccasion[]|array
     */
    public function getOccasions()
    {
        return $this->occasions;
    }
    
    /**
     * @return Viand
     */
    public function getViand(): Viand
    {
        return $this->viand;
    }
    
    
}