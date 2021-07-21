<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Mail;


interface SupportsRelatedEmailsInterface
{
    
    /**
     * Get entity id of tracked related entity
     *
     * @return int|null
     */
    public function getId(): ?int;
}