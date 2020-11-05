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


class AccumulatedIngredientOccasion
{
    
    /**
     * Unit
     *
     * @see QuantityUnit
     */
    private $unit;
    
    /**
     * Amounts
     *
     * @var array|float[]
     */
    private $amounts = [];
    
    /**
     * AccumulatedIngredient constructor.
     *
     * @param QuantityUnit $unit
     * @param float $amount
     */
    public function __construct(QuantityUnit $unit, ?float $amount = null)
    {
        $this->unit = $unit;
        if ($amount !== null) {
            $this->amounts[] = $amount;
        }
    }
    
    /**
     * @return QuantityUnit
     */
    public function getUnit(): QuantityUnit
    {
        return $this->unit;
    }
    
    /**
     * Get amount sum
     *
     * @return float
     */
    public function getAmount(): float
    {
        return array_sum($this->amounts);
    }
    
    /**
     * @return array|float[]
     */
    public function getAmounts()
    {
        return $this->amounts;
    }
    
    /**
     * Store an amount
     *
     * @param float $amount
     */
    public function addAmount(float $amount): void
    {
        
        $this->amounts[] = $amount;
    }
    
    
}