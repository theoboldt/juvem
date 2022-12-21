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

use Doctrine\ORM\Mapping as ORM;

trait SoftDeleteTrait
{

    /**
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     */
    protected $deletedAt = null;

    /**
     * Set deletedAt
     *
     * @param \DateTime|null $deletedAt
     * @return void
     */
    public function setDeletedAt(?\DateTimeInterface $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime|null
     */
    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    /**
     * Determine if entity is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }


}
