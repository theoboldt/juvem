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
     * Reason for this summand
     *
     * @var SummandCausableInterface|null
     */
    protected $cause;
    
    /**
     * BaseSummand constructor.
     *
     * @param PriceTaggableEntityInterface $entity Entity for which this summand is valuable
     * @param SummandCausableInterface|null $cause Reason for this summand if it's not the same as $entity
     */
    public function __construct(PriceTaggableEntityInterface $entity, SummandCausableInterface $cause = null)
    {
        $this->entity = $entity;
        if (!$cause && !$this->entity instanceof SummandCausableInterface) {
            throw new \InvalidArgumentException(
                'When not passing cause, entity must implement SummandCausableInterface'
            );
        }
        $this->cause = $cause;
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
     * {@inheritdoc}
     */
    public function getCause(): SummandCausableInterface
    {
        if ($this->cause) {
            return $this->cause;
        } else {
            return $this->entity;
        }
    }
}
