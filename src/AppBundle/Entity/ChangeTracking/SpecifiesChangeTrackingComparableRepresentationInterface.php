<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\ChangeTracking;


interface SpecifiesChangeTrackingComparableRepresentationInterface
{
    
    /**
     * Get a representation of this object which can be compared in terms of change tracking
     *
     * @return int|string|null|float
     */
    public function getComparableRepresentation();
    
    
}