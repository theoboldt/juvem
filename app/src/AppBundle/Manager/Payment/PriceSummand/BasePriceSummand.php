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


use AppBundle\Entity\Participant;

class BasePriceSummand extends BaseSummand implements SummandInterface
{

    /**
     * Related participant (not available for employees)
     *
     * @var Participant
     */
    protected $impacted;

    /**
     * BaseSummand constructor.
     *
     * @param Participant $impacted Entity for which this summand is valuable
     */
    public function __construct(Participant $impacted)
    {
        parent::__construct($impacted, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'Grundpreis';
    }

    /**
     * Get price
     *
     * @param bool $inEuro If set to true, value is returned in euro
     * @return float|int
     */
    public function getValue($inEuro = false)
    {
        $price = $this->impacted->getBasePrice($inEuro);
        if ($price === null) {
            throw new \InvalidArgumentException('Base price is null');
        }
        return $price;
    }
}
