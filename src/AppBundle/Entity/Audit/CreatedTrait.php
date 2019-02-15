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
use JMS\Serializer\Annotation as Serialize;

/**
 * Trait CreatedTrait
 *
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 * @package AppBundle\Entity\Audit
 */
trait CreatedTrait
{

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created_at")
     * @Serialize\Expose
     * @Serialize\Type("DateTime<'d.m.Y'>")
     */
    protected $createdAt;

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Set createdAt to now
     *
     * @ORM\PrePersist
     */
    public function setCreatedAtNow()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}