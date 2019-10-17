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
    private $originalAmount;
    
    /**
     * Id of unit of amount
     *
     * @var int
     */
    private $unitId;
    
    /**
     * Feedback for this ingredient
     *
     * @var int|null
     */
    protected $feedback = RecipeFeedback::AMOUNT_OK;
    
    
    /**
     * If set, corrected recommended amount value
     *
     * @var null|float
     */
    private $correctedAmount = null;
    
    /**
     * RecipeIngredientFeedback constructor.
     *
     * @param int $recipeIngredientId
     * @param float $originalAmount
     * @param int $unitId
     * @param null|int $feedback
     * @param float|null $correctedAmount
     */
    public function __construct(
        int $recipeIngredientId,
        float $originalAmount,
        int $unitId,
        ?int $feedback = RecipeFeedback::AMOUNT_OK,
        ?float $correctedAmount = null
    )
    {
        $this->recipeIngredientId = $recipeIngredientId;
        $this->originalAmount     = $originalAmount;
        $this->unitId             = $unitId;
        $this->feedback           = $feedback;
        $this->correctedAmount    = $correctedAmount;
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
    public function getOriginalAmount(): float
    {
        return $this->originalAmount;
    }
    
    /**
     * @param float $originalAmount
     * @return RecipeIngredientFeedback
     */
    public function setOriginalAmount(float $originalAmount): RecipeIngredientFeedback
    {
        $this->originalAmount = $originalAmount;
        return $this;
    }
    
    /**
     * @return int
     */
    public function getUnitId(): int
    {
        return $this->unitId;
    }
    
    /**
     * @param int $unitId
     * @return RecipeIngredientFeedback
     */
    public function setUnitId(int $unitId): RecipeIngredientFeedback
    {
        $this->unitId = $unitId;
        return $this;
    }
    
    /**
     * @return int|null
     */
    public function getFeedback(): ?int
    {
        return $this->feedback;
    }
    
    /**
     * @param int|null $feedback
     * @return RecipeIngredientFeedback
     */
    public function setFeedback($feedback): RecipeIngredientFeedback
    {
        if (empty($feedback)) {
            $feedback = null;
        } else {
            $feedback = (int)$feedback;
        }
        
        $this->feedback = $feedback;
        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getCorrectedAmount(): ?float
    {
        return $this->correctedAmount;
    }
    
    /**
     * @param float|null $correctedAmount
     * @return RecipeIngredientFeedback
     */
    public function setCorrectedAmount(?float $correctedAmount = null): RecipeIngredientFeedback
    {
        $this->correctedAmount = $correctedAmount;
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
            'iid'             => $this->recipeIngredientId,
            'originalAmount'  => $this->originalAmount,
            'correctedAmount' => $this->correctedAmount,
            'uid'             => $this->unitId,
            'feedback'        => $this->feedback,
        ];
    }
}