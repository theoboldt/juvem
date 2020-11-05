<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Geo;


interface AddressResolverInterface
{
    /**
     * Provide object providing coordination information for element if found
     *
     * @param AddressAwareInterface $item Address aware interface
     * @return CoordinatesAwareInterface|null Coordinate providing object or null if not found
     */
    public function provideCoordinates(AddressAwareInterface $item): ?CoordinatesAwareInterface;
    
}