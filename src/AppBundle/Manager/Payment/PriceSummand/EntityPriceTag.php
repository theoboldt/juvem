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
     * @var PriceTaggableEntityInterface
     */
    protected $entity;

    /**
     * Attached summands
     *
     * @var array|SummandInterface[]
     */
    protected $summands;

    /**
     * BaseSummand constructor.
     *
     * @param PriceTaggableEntityInterface $entity   Related entity
     * @param array|SummandInterface[]     $summands Summands
     */
    public function __construct(PriceTaggableEntityInterface $entity, array $summands)
    {
        $this->entity   = $entity;
        $this->summands = $summands;
    }

    /**
     * Get related participant
     *
     * @return PriceTaggableEntityInterface
     */
    public function getEntity(): PriceTaggableEntityInterface
    {
        return $this->entity;
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
     * @return float|int|null
     */
    public function getPrice() {
        if (!count($this->summands)) {
            return null;
        }
        $price = 0;
        foreach ($this->summands as $summand) {
            $price += $summand->getValue();
        }
        return $price;
    }

}
