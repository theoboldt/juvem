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


class RecipeIngredientFeedback implements \JsonSerializable
{
    
    /**
     * Related recipe ingredient id of {@see RecipeIngredient}
     *
     * @var int
     */
    private $recipeIngredientId;
    
    /**
     * Amount of {@see RecipeIngredient} at time of feedback collection (to ensure that factor change is reflected over time)
     *
     * @var float
     */
    private $amountOriginal;
    
    /**
     * Id of unit of amount of original
     *
     * @var int
     */
    private $unitIdOriginal;
    
    /**
     * Feedback for this ingredient
     *
     * @var int|null
     */
    protected $ingredientFeedback = RecipeFeedback::AMOUNT_OK;
    
    /**
     * If set, corrected recommended amount value
     *
     * @var null|float
     */
    private $amountCorrected = null;
    
    /**
     * Id of unit of amount of correction
     *
     * @var int
     */
    private $unitIdCorrected;
    
    /**
     * Create new instance from array
     *
     * @param array $feedback Array Data
     * @return RecipeIngredientFeedback
     */
    public static function createFromArray(array $feedback): RecipeIngredientFeedback
    {
        return new self(
            $feedback['iid'],
            $feedback['amountOriginal'] ?? null,
            $feedback['uidOriginal'] ?? null,
            $feedback['ingredientFeedback'] ?? null,
            $feedback['amountCorrected'] ?? null,
            $feedback['uidCorrected'] ?? null
        );
    }
    
    /**
     * Create new instance for Ingredient
     *
     * @param RecipeIngredient $ingredient
     * @return RecipeIngredientFeedback
     */
    public static function createFromIngredient(RecipeIngredient $ingredient): RecipeIngredientFeedback
    {
        return new self(
            $ingredient->getId(),
            $ingredient->getAmount(),
            $ingredient->getUnit()->getId(),
            null,
            null,
            null
        );
    }
    
    /**
     * RecipeIngredientFeedback constructor.
     *
     * @param int $recipeIngredientId
     * @param float $amountOriginal
     * @param int $unitIdOriginal
     * @param null|int $ingredientFeedback
     * @param float|null $amountCorrected
     * @param int|null $unitIdCorrected
     */
    public function __construct(
        int $recipeIngredientId,
        float $amountOriginal,
        int $unitIdOriginal,
        ?int $ingredientFeedback = RecipeFeedback::AMOUNT_OK,
        ?float $amountCorrected = null,
        ?int $unitIdCorrected = null
    )
    {
        $this->recipeIngredientId = $recipeIngredientId;
        $this->amountOriginal     = $amountOriginal;
        $this->unitIdOriginal     = $unitIdOriginal;
        $this->ingredientFeedback = $ingredientFeedback;
        $this->amountCorrected    = $amountCorrected;
        $this->unitIdCorrected    = $unitIdCorrected;
    }
    
    /**
     * @return int
     */
    public function getRecipeIngredientId(): int
    {
        return $this->recipeIngredientId;
    }
    
    /**
     * @param int $recipeIngredientId
     * @return RecipeIngredientFeedback
     */
    public function setRecipeIngredientId(int $recipeIngredientId): RecipeIngredientFeedback
    {
        $this->recipeIngredientId = $recipeIngredientId;
        return $this;
    }
    
    /**
     * @return float
     */
    public function getAmountOriginal(): float
    {
        return $this->amountOriginal;
    }
    
    /**
     * @param float $amountOriginal
     * @return RecipeIngredientFeedback
     */
    public function setAmountOriginal(float $amountOriginal): RecipeIngredientFeedback
    {
        $this->amountOriginal = $amountOriginal;
        return $this;
    }
    
    /**
     * @return int
     */
    public function getUnitIdOriginal(): int
    {
        return $this->unitIdOriginal;
    }
    
    /**
     * @param int $unitIdOriginal
     * @return RecipeIngredientFeedback
     */
    public function setUnitIdOriginal(int $unitIdOriginal): RecipeIngredientFeedback
    {
        $this->unitIdOriginal = $unitIdOriginal;
        return $this;
    }
    
    /**
     * @return int|null
     */
    public function getIngredientFeedback(): ?int
    {
        return $this->ingredientFeedback;
    }
    
    
    /**
     * Get feedback translated
     *
     * @return string
     */
    public function getIngredientFeedbackLabel(): string {
        return RecipeFeedback::formatFeedback($this->ingredientFeedback);
    }
    
    /**
     * @param int|null $feedback
     * @return RecipeIngredientFeedback
     */
    public function setIngredientFeedback($feedback): RecipeIngredientFeedback
    {
        if (empty($feedback)) {
            $feedback = null;
        } else {
            $feedback = (int)$feedback;
        }
        
        $this->ingredientFeedback = $feedback;
        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getAmountCorrected(): ?float
    {
        return $this->amountCorrected;
    }
    
    /**
     * @param float|null $amountCorrected
     * @return RecipeIngredientFeedback
     */
    public function setAmountCorrected(?float $amountCorrected = null): RecipeIngredientFeedback
    {
        $this->amountCorrected = $amountCorrected;
        return $this;
    }
    
    /**
     * @return null|int
     */
    public function getUnitIdCorrected(): ?int
    {
        return $this->unitIdCorrected;
    }
    
    /**
     * @param int $unitIdCorrected
     * @return RecipeIngredientFeedback
     */
    public function setUnitIdCorrected(int $unitIdCorrected): RecipeIngredientFeedback
    {
        $this->unitIdCorrected = $unitIdCorrected;
        return $this;
    }
    
    /**
     * Specify data which should be serialized to JSON
     *
     * @link  https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'iid'                => $this->recipeIngredientId,
            'amountOriginal'     => $this->amountOriginal,
            'uidOriginal'        => $this->unitIdOriginal,
            'amountCorrected'    => $this->amountCorrected,
            'uidCorrected'       => $this->unitIdCorrected,
            'ingredientFeedback' => $this->ingredientFeedback,
        ];
    }
}