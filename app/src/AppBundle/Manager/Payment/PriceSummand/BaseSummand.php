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
     * @var SummandImpactedInterface
     */
    protected $impacted;
    
    /**
     * Reason for this summand
     *
     * @var SummandCausableInterface|null
     */
    protected $cause;
    
    /**
     * BaseSummand constructor.
     *
     * @param SummandImpactedInterface $impacted Entity for which this summand is valuable
     * @param SummandCausableInterface|null $cause Reason for this summand if it's not the same as $entity
     */
    public function __construct(SummandImpactedInterface $impacted, SummandCausableInterface $cause = null)
    {
        $this->impacted = $impacted;
        if (!$cause && !$this->impacted instanceof SummandCausableInterface) {
            throw new \InvalidArgumentException(
                'When not passing cause, entity must implement SummandCausableInterface'
            );
        }
        $this->cause = $cause;
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
     * {@inheritdoc}
     */
    public function getCause(): SummandCausableInterface
    {
        if ($this->cause) {
            return $this->cause;
        } else {
            return $this->impacted;
        }
    }
}
