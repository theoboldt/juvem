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


interface ProvidesModifiedInterface
{
    /**
     * Get modified at date or null
     *
     * @return \DateTimeInterface|null
     */
    public function getModifiedAt(): ?\DateTimeInterface;
    
}