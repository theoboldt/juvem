<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Payment\PriceSummand;


class EntityPriceTag
{

    /**
     * Related participant
     *
     * @var SummandImpactedInterface
     */
    protected $impacted;

    /**
     * Attached summands
     *
     * @var array|SummandInterface[]
     */
    protected $summands;

    /**
     * BaseSummand constructor.
     *
     * @param SummandImpactedInterface $impacted   Related entity
     * @param array|SummandInterface[]     $summands Summands
     */
    public function __construct(SummandImpactedInterface $impacted, array $summands)
    {
        $this->impacted = $impacted;
        $this->summands = $summands;
    }

    /**
     * Get related participant
     *
     * @return SummandImpactedInterface
     */
    public function getImpacted(): SummandImpactedInterface
    {
        return $this->impacted;
    }

    /**
     * Get attached summands
     *
     * @return SummandInterface[]|array
     */
    public function getSummands()
    {
        return $this->summands;
    }

    /**
     * Get price tag
     *
     * @param bool $inEuro If set to true, value is returned in euro
     * @return float|int|null
     */
    public function getPrice($inEuro = false) {
        if (!count($this->summands)) {
            return null;
        }
        $price = 0;
        foreach ($this->summands as $summand) {
            $price += $summand->getValue($inEuro);
        }
        return $price;
    }

}
