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


interface SpecifiesChangeTrackingStorableRepresentationInterface
{
    
    /**
     * Provides value which can be stored as JSON for change tracking
     *
     * @return array|string|int|float|null
     */
    public function getChangeTrackingStorableRepresentation();
    
}