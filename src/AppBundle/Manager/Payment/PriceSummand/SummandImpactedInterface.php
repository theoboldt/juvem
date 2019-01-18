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


interface SummandImpactedInterface extends SummandCausableInterface
{

    /**
     * Get nameFirst
     *
     * @return string
     */
    public function getNameFirst();

    /**
     * Get nameLast
     *
     * @return string
     */
    public function getNameLast();

    /**
     * Get fullname
     *
     * @return string
     */
    public function fullname();
}
