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

trait CreatorModifierTrait
{
    use CreatorTrait;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="uid", onDelete="SET NULL")
     *
     * @var User|null
     */
    protected $modifiedBy = null;

    /**
     * Set modifiedBy
     *
     * @param User|null $modifiedBy
     *
     * @return self
     */
    public function setModifiedBy(User $modifiedBy = null)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return User
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }
}