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
    protected $entity;

    /**
     * BaseSummand constructor.
     *
     * @param Participant $entity
     */
    public function __construct(Participant $entity)
    {
        parent::__construct($entity);
    }

    /**
     * Get price in euro cent
     *
     * @return float|int
     */
    public function getValue()
    {
        $price = $this->entity->getPrice();
        if ($price === null) {
            throw new \InvalidArgumentException('Base price is null');
        }
        return $price;
    }
}
