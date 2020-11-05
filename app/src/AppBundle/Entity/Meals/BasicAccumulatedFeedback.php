<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\Meals;


abstract class BasicAccumulatedFeedback implements AccumulatedFeedbackProviderInterface
{
    
    /**
     * Get amount of way too less probes
     *
     * @return int
     */
    abstract public function getWayTooLess(): int;
    
    /**
     * Get amount of too less probes
     *
     * @return int
     */
    abstract public function getTooLess(): int;
    
    /**
     * Get amount of ok probes
     *
     * @return int
     */
    abstract public function getOk(): int;
    
    /**
     * Get amount of way much less probes
     *
     * @return int
     */
    abstract public function getTooMuch(): int;
    
    /**
     * Get amount of way too much probes
     *
     * @return int
     */
    abstract public function getWayTooMuch(): int;
    
    /**
     * Get total probes count
     *
     * @return int
     */
    public function getTotal(): int
    {
        return $this->getWayTooLess()
               + $this->getTooLess()
               + $this->getOk()
               + $this->getTooMuch()
               + $this->getWayTooMuch();
    }
    
    /**
     * Get total probes count
     *
     * @return int
     */
    public function getMax(): int
    {
        return max(
            $this->getWayTooLess(),
            $this->getTooLess(),
            $this->getOk(),
            $this->getTooMuch(),
            $this->getWayTooMuch()
        );
    }
    
    /**
     * {@inheritDoc}
     */
    abstract public function getProbeCount(): int;
}