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


class IngredientAccumulatedFeedback extends BasicFeedbackBasedFeedback implements AccumulatedFeedbackProviderInterface
{
    /**
     * ingredientId
     *
     * @var int
     */
    private $ingredientId;
    
    /**
     * Ingredient
     *
     * @var RecipeIngredient
     */
    public $ingredient;
    
    /**
     * IngredientAccumulatedFeedback constructor.
     *
     * @param RecipeFeedback[]|array $items
     * @param RecipeIngredient $ingredient
     */
    public function __construct(array $items, RecipeIngredient $ingredient)
    {
        $this->ingredientId = $ingredient->getId();
        $this->ingredient   = $ingredient;
        parent::__construct($items);
    }
    
    /**
     * Process Feedback items
     */
    protected function processFeedbackItems(): void
    {
        $this->wayTooLess = 0;
        $this->tooLess    = 0;
        $this->ok         = 0;
        $this->tooMuch    = 0;
        $this->wayTooMuch = 0;
        
        
        foreach ($this->items as $recipeFeedback) {
            $factor = $recipeFeedback->getWeight();
            if ($factor === 0) {
                continue;
            }
            foreach ($recipeFeedback->getFeedback() as $ingredientFeedback) {
                if ($this->ingredientId === $ingredientFeedback->getRecipeIngredientId()) {
                    if ($ingredientFeedback->getIngredientFeedback() === null) {
                        continue;
                    }
                    switch ($ingredientFeedback->getIngredientFeedback()) {
                        case RecipeFeedback::AMOUNT_WAY_TOO_LESS:
                            $this->wayTooLess += ($factor);
                            break;
                        case RecipeFeedback::AMOUNT_TOO_LESS:
                            $this->tooLess += ($factor);
                            break;
                        case RecipeFeedback::AMOUNT_OK:
                            $this->ok += ($factor);
                            break;
                        case RecipeFeedback::AMOUNT_TOO_MUCH:
                            $this->tooMuch += ($factor);
                            break;
                        case RecipeFeedback::AMOUNT_WAY_TOO_MUCH:
                            $this->wayTooMuch += ($factor);
                            break;
                    }
                }
            }
        }
    }
}