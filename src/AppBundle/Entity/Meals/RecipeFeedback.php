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
use AppBundle\Entity\Event;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="recipe_feedback")
 * @ORM\HasLifecycleCallbacks()
 */
class RecipeFeedback
{
    use CreatedModifiedTrait, CreatorModifierTrait;
    
    const WEIGHT_NONE       = 0;
    const WEIGHT_NONE_LABEL = 'Nicht werten';
    
    const WEIGHT_SINGLE       = 1;
    const WEIGHT_SINGLE_LABEL = 'Normale Wertung';
    
    const WEIGHT_DOUBLE       = 2;
    const WEIGHT_DOUBLE_LABEL = 'Doppelte Gewichtung';
    
    const AMOUNT_WAY_TOO_LESS       = -2;
    const AMOUNT_WAY_TOO_LESS_LABEL = 'viel zu wenig';
    
    const AMOUNT_TOO_LESS       = -1;
    const AMOUNT_TOO_LESS_LABEL = 'zu wenig';
    
    const AMOUNT_OK       = 0;
    const AMOUNT_OK_LABEL = 'angemessen';
    
    const AMOUNT_TOO_MUCH       = 1;
    const AMOUNT_TOO_MUCH_LABEL = 'zu viel';
    
    const AMOUNT_WAY_TOO_MUCH       = 2;
    const AMOUNT_WAY_TOO_MUCH_LABEL = 'viel zu viel';
    
    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\Meals\Recipe", inversedBy="feedbacks", cascade={"persist"})
     * @ORM\JoinColumn(name="rid", referencedColumnName="id", onDelete="cascade")
     * @var Recipe
     */
    protected $recipe;
    
    /**
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\Event", cascade={"persist"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="set null", nullable=true)
     * @var Event|null
     */
    protected $event = null;
    
    /**
     * Amount of mouths fed with this recipe
     *
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @Assert\NotBlank()
     * @var int
     */
    protected $peopleCount;
    
    /**
     * Date of cooking or when feedback was generated
     *
     * @ORM\Column(type="date", name="occurrence_date")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     * @var \DateTime
     */
    protected $date;
    
    /**
     * Weight factor of this feedback, zero for no weight
     *
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     * @Assert\Choice({"0", "1", "2"})
     * @Assert\NotBlank()
     */
    protected $weight = self::WEIGHT_SINGLE;
    
    /**
     * @ORM\Column(type="text", options={"default":""})
     * @var string
     */
    protected $comment = '';
    
    /**
     * Global feedback for the recipe
     *
     * @ORM\Column(type="smallint")
     * @Assert\Choice({"-2", "-1", "0", "1", "2"})
     * @Assert\NotBlank()
     */
    protected $feedbackGlobal = self::AMOUNT_OK;
    
    /**
     * Complex feedback structure for each viand
     *
     * @ORM\Column(type="json_array", length=16777215, name="feedback")
     */
    protected $feedback = [];
    
    /**
     * RecipeFeedback constructor.
     *
     * @param Recipe $recipe
     * @param null|Event $event
     */
    public function __construct(Recipe $recipe, ?Event $event = null)
    {
        $this->recipe    = $recipe;
        $this->event     = $event;
        $this->createdAt = new \DateTime();
        $this->setDate(new \DateTime());
    }
    
    
    /**
     * @return int
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
     * @return RecipeFeedback
     */
    public function setRecipe(Recipe $recipe): RecipeFeedback
    {
        $this->recipe = $recipe;
        return $this;
    }
    
    /**
     * @return Event|null
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }
    
    /**
     * @param Event|null $event
     * @return RecipeFeedback
     */
    public function setEvent(?Event $event = null): RecipeFeedback
    {
        $this->event = $event;
        return $this;
    }
    
    /**
     * @return null|int
     */
    public function getPeopleCount(): ?int
    {
        return $this->peopleCount;
    }
    
    /**
     * @param int $peopleCount
     * @return RecipeFeedback
     */
    public function setPeopleCount(int $peopleCount): RecipeFeedback
    {
        $this->peopleCount = $peopleCount;
        return $this;
    }
    
    /**
     * @return \DateTime
     */
    public function getDate()
    {
        $this->date->setTime(10, 0, 0);
        return $this->date;
    }
    
    /**
     * @param \DateTime $date
     * @return RecipeFeedback
     */
    public function setDate($date)
    {
        $date->setTime(10, 0, 0);
        $this->date = $date;
        return $this;
    }
    
    /**
     * @param bool $asLabel
     * @return int|string
     */
    public function getWeight($asLabel = false)
    {
        if ($asLabel) {
            switch ($this->weight) {
                case self::WEIGHT_NONE:
                    return self::WEIGHT_NONE_LABEL;
                case self::WEIGHT_SINGLE:
                    return self::WEIGHT_SINGLE_LABEL;
                case self::WEIGHT_DOUBLE:
                    return self::WEIGHT_DOUBLE_LABEL;
            }
        }
        return $this->weight;
    }
    
    /**
     * @param int $weight
     * @return RecipeFeedback
     */
    public function setWeight(int $weight): RecipeFeedback
    {
        $this->weight = $weight;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
    
    /**
     * @param string $comment
     * @return RecipeFeedback
     */
    public function setComment(string $comment = ''): RecipeFeedback
    {
        $this->comment = $comment;
        return $this;
    }
    
    /**
     * @param bool $asLabel
     * @return int|string|null
     */
    public function getFeedbackGlobal($asLabel = false)
    {
        if ($asLabel) {
            switch ($this->feedbackGlobal) {
                case self::AMOUNT_WAY_TOO_LESS:
                    return self::AMOUNT_WAY_TOO_LESS_LABEL;
                case self::AMOUNT_TOO_LESS:
                    return self::AMOUNT_TOO_LESS_LABEL;
                case self::AMOUNT_OK:
                    return self::AMOUNT_OK_LABEL;
                case self::AMOUNT_TOO_MUCH:
                    return self::AMOUNT_TOO_MUCH_LABEL;
                case self::AMOUNT_WAY_TOO_MUCH:
                    return self::AMOUNT_WAY_TOO_MUCH_LABEL;
            }
        }
        return $this->feedbackGlobal;
    }
    
    /**
     * @param mixed $feedbackGlobal
     * @return RecipeFeedback
     */
    public function setFeedbackGlobal($feedbackGlobal)
    {
        $this->feedbackGlobal = $feedbackGlobal;
        return $this;
    }
    
    /**
     * Get feedback list
     *
     * @return array|RecipeIngredientFeedback[]
     */
    public function getFeedback(): array
    {
        $feedbackList = [];
        foreach ($this->feedback as $feedback) {
            $feedbackList[$feedback['iid']] = RecipeIngredientFeedback::createFromArray($feedback);
        }
        $recipe = $this->getRecipe();
        if ($recipe) {
            foreach ($recipe->getIngredients() as $ingredient) {
                if (!isset($feedbackList[$ingredient->getId()])) {
                    $ingredientFeedback                 = RecipeIngredientFeedback::createFromIngredient($ingredient);
                    $feedbackList[$ingredient->getId()] = $ingredientFeedback;
                }
            }
            $this->setFeedback(array_values($feedbackList));
        }
        
        return array_values($feedbackList);
    }
    
    /**
     * Set feedback items
     *
     * @param array $feedbackList
     * @return RecipeFeedback
     */
    public function setFeedback(array $feedbackList): RecipeFeedback
    {
        $feedback = null;
        foreach ($feedbackList as &$feedback) {
            if ($feedback instanceof RecipeIngredientFeedback) {
                $feedback = $feedback->jsonSerialize();
            }
        }
        unset($feedback);
        $this->feedback = $feedbackList;
        return $this;
    }
    
    public function addFeedback(RecipeIngredientFeedback $feedback)
    {
        $this->feedback[$feedback->getRecipeIngredientId()] = $feedback->jsonSerialize();
    }
    
    
}