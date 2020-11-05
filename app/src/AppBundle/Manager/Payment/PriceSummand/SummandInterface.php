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
     * @param bool $inEuro If set to true, value is returned in euro
     * @return float|int
     */
    public function getValue($inEuro = false);

    /**
     * Get short textual description of this summand's kind
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get related entity
     *
     * @return SummandImpactedInterface
     */
    public function getImpacted(): SummandImpactedInterface;

    /**
     * Get cause for this summand, which might differ from value of @see getImpacted()
     *
     * Get cause for this summand, which might differ from value of @see getImpacted(), for example if this is an
     *
     * @see Participant and the cause for this summand is because of a @see Participation fillout
     *
     * @return SummandCausableInterface
     */
    public function getCause(): SummandCausableInterface;
}
