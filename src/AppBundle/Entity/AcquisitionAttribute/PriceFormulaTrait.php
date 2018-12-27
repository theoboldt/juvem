<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\AcquisitionAttribute;


trait PriceFormulaTrait
{
    /**
     * If set, contains a formula which has an effect on the price
     *
     * @ORM\Column(type="string", length=255, name="price_formula", nullable=true)
     */
    protected $priceFormula = null;
    
    /**
     * Get price formula if set
     *
     * @return string|null
     */
    public function getPriceFormula(): ?string
    {
        return $this->priceFormula;
    }
    
    /**
     * Set new price formula
     *
     * @param string|null $priceFormula Textual formula
     * @return $this
     */
    public function setPriceFormula(string $priceFormula = null)
    {
        $this->priceFormula = $priceFormula;
        return $this;
    }
    
    
}