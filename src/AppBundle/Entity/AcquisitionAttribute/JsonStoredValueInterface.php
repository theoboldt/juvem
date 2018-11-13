<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\AcquisitionAttribute;


interface JsonStoredValueInterface
{
    /**
     * Get json decoded value as array
     *
     * @return array
     */
    public function getValueAsArray(): array;

}
