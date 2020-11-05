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


interface AccumulatedFeedbackProviderInterface
{
    
    /**
     * Get amount of way too less probes
     *
     * @return int
     */
    public function getWayTooLess(): int;
    
    /**
     * Get amount of too less probes
     *
     * @return int
     */
    public function getTooLess(): int;
    
    /**
     * Get amount of ok probes
     *
     * @return int
     */
    public function getOk(): int;
    
    /**
     * Get amount of way much less probes
     *
     * @return int
     */
    public function getTooMuch(): int;
    
    /**
     * Get amount of way too much probes
     *
     * @return int
     */
    public function getWayTooMuch(): int;
    
    /**
     * Get weighted total
     *
     * @return int
     */
    public function getTotal(): int;
    
    /**
     * Get highest value
     *
     * @return int
     */
    public function getMax(): int;
    
    /**
     * Get amount of probes considered (because of weighting this might differ from {@see getTotal()}
     *
     * @return int
     */
    public function getProbeCount(): int;
}