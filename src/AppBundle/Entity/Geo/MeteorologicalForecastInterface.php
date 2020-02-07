<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\Geo;


interface MeteorologicalForecastInterface
    // extends \Traversable TODO implement traversable for PHP 7.4
{
    
    /**
     * Get begin of validity of forecast information
     *
     * @return \DateTimeInterface
     */
    public function getValidSince(): \DateTimeInterface;
    
    /**
     * Get end of validity of forecast information
     *
     * @return \DateTimeInterface
     */
    public function getValidUntil(): \DateTimeInterface;
    
    
    /**
     * Get forecast elements
     *
     * @return \Traversable|ClimaticInformationInterface[]
     */
    public function getElements(): \Traversable;
    
}