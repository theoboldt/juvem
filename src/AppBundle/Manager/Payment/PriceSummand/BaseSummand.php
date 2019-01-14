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

abstract class BaseSummand
{

    /**
     * Related participant
     *
     * @var PriceTaggableEntityInterface
     */
    protected $entity;

    /**
     * BaseSummand constructor.
     *
     * @param PriceTaggableEntityInterface $entity
     */
    public function __construct(PriceTaggableEntityInterface $entity)
    {
        $this->entity = $entity;
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

}
