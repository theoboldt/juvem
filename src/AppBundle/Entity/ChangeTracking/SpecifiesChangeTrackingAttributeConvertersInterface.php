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


interface SpecifiesChangeTrackingAttributeConvertersInterface
{
    
    /**
     * Specifies a list of callables responsible to convert the value to a presentable form
     *
     * Specifies a list of callables responsible to convert the value to a presentable form for the attribute
     * using the same name as the key of the callables item
     *
     * @return array|callable[]
     */
    public function getChangeTrackingAttributeConverters(): array;
}