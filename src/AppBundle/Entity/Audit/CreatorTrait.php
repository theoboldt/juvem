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

use AppBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait CreatorTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="uid", onDelete="SET NULL")
     *
     * @var User|null
     */
    protected $createdBy = null;

    /**
     * Set createdBy
     *
     * @param User|null $createdBy
     *
     * @return self
     */
    public function setCreatedBy(User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return User|null
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
}