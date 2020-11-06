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


class RecipeAccumulatedGlobalFeedback extends BasicFeedbackBasedFeedback implements AccumulatedFeedbackProviderInterface
{
    /**
     * Recipe
     *
     * @var Recipe
     */
    public $recipe;

    /**
     * IngredientAccumulatedFeedback constructor.
     *
     * @param RecipeFeedback[]|array $items
     * @param Recipe                 $recipe
     */
    public function __construct(array $items, Recipe $recipe)
    {
        $this->recipe = $recipe;
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
            switch ($recipeFeedback->getFeedbackGlobal()) {
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

    /**
     * @return Recipe
     */
    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }
}
