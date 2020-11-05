<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;


interface EntityHavingPhoneNumbersInterface
{
    
    /**
     * Get phone Numbers
     */
    public function getPhoneNumbers();
    
    /**
     * Add phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     */
    public function addPhoneNumber(PhoneNumber $phoneNumber);
    
    /**
     * Remove phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     */
    public function removePhoneNumber(PhoneNumber $phoneNumber);
}