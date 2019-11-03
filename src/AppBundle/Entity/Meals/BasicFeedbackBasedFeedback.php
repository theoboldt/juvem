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


abstract class BasicFeedbackBasedFeedback extends BasicAccumulatedFeedback
{
    
    /**
     * List of feedback items
     *
     * @var array|RecipeFeedback[]
     */
    protected $items = [];
    
    protected $wayTooLess = null;
    
    protected $tooLess = null;
    
    protected $ok = null;
    
    protected $tooMuch = null;
    
    protected $wayTooMuch = null;
    
    /**
     * IngredientAccumulatedFeedback constructor.
     *
     * @param RecipeFeedback[]|array $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }
    
    /**
     * Get amount of way too less probes
     *
     * @return int
     */
    public function getWayTooLess(): int
    {
        if ($this->wayTooLess === null) {
            $this->processFeedbackItems();
        }
        return $this->wayTooLess;
    }
    
    /**
     * Get amount of too less probes
     *
     * @return int
     */
    public function getTooLess(): int
    {
        if ($this->tooLess === null) {
            $this->processFeedbackItems();
        }
        return $this->tooLess;
    }
    
    /**
     * Get amount of ok probes
     *
     * @return int
     */
    public function getOk(): int
    {
        if ($this->ok === null) {
            $this->processFeedbackItems();
        }
        return $this->ok;
    }
    
    /**
     * Get amount of way much less probes
     *
     * @return int
     */
    public function getTooMuch(): int
    {
        if ($this->tooMuch === null) {
            $this->processFeedbackItems();
        }
        return $this->tooMuch;
    }
    
    /**
     * Get amount of way too much probes
     *
     * @return int
     */
    public function getWayTooMuch(): int
    {
        if ($this->wayTooMuch === null) {
            $this->processFeedbackItems();
        }
        return $this->wayTooMuch;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getProbeCount(): int
    {
        return count($this->items);
    }

    /**
     * @return RecipeFeedback[]|array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Process Feedback items
     *
     * @return void
     */
    abstract protected function processFeedbackItems(): void;
}
