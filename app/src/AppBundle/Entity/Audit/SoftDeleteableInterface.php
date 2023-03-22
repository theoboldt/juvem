<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\Audit;


interface SoftDeleteableInterface extends \Gedmo\SoftDeleteable\SoftDeleteable
{
    
    /**
     * Determine if entity is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool;
    
    /**
     * Get deletedAt
     *
     * @return \DateTimeInterface|null
     */
    public function getDeletedAt(): ?\DateTimeInterface;
}
