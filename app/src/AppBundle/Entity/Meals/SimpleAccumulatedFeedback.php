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


class SimpleAccumulatedFeedback extends BasicAccumulatedFeedback implements AccumulatedFeedbackProviderInterface
{
    public $wayTooLess = 0;
    
    public $tooLess = 0;
    
    public $ok = 0;
    
    public $tooMuch = 0;
    
    public $wayTooMuch = 0;
    
    /**
     * Get amount of way too less probes
     *
     * @return int
     */
    public function getWayTooLess(): int
    {
        return $this->wayTooLess;
    }
    
    /**
     * Get amount of too less probes
     *
     * @return int
     */
    public function getTooLess(): int
    {
        return $this->tooLess;
    }
    
    /**
     * Get amount of ok probes
     *
     * @return int
     */
    public function getOk(): int
    {
        return $this->ok;
    }
    
    /**
     * Get amount of way much less probes
     *
     * @return int
     */
    public function getTooMuch(): int
    {
        return $this->tooMuch;
    }
    
    /**
     * Get amount of way too much probes
     *
     * @return int
     */
    public function getWayTooMuch(): int
    {
        return $this->wayTooMuch;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getProbeCount(): int
    {
        return $this->getTotal();
    }
    
}