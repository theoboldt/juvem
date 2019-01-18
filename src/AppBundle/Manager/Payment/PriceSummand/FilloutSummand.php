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


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Participant;

class FilloutSummand extends BaseSummand implements SummandInterface, AttributeAwareInterface
{

    /**
     * Fillout
     *
     * @var Fillout
     */
    protected $fillout;

    /**
     * Get price in euro cent
     *
     * @var float|int
     */
    protected $value;
    
    /**
     * FilloutSummand constructor.
     *
     * @param PriceTaggableEntityInterface $entity
     * @param Fillout $fillout
     * @param float|int $value
     */
    public function __construct(PriceTaggableEntityInterface $entity, Fillout $fillout, $value)
    {
        $this->fillout = $fillout;
        $this->value   = $value;
        parent::__construct($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get related attribute
     *
     * @return Attribute
     */
    public function getAttribute(): Attribute
    {
        return $this->fillout->getAttribute();
    }

    /**
     * Get related fillout
     *
     * @return Fillout
     */
    public function getFillout(): Fillout
    {
        return $this->fillout;
    }

}
