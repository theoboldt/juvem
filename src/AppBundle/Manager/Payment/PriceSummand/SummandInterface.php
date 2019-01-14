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


interface SummandInterface
{

    /**
     * Get price in euro cent
     *
     * @return float|int
     */
    public function getValue();

    /**
     * Get related entity
     *
     * @return PriceTaggableEntityInterface
     */
    public function getEntity(): PriceTaggableEntityInterface;
}
