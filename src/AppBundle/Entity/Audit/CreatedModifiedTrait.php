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
 * Trait CreatedModifiedTrait
 *
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 * @package AppBundle\Entity\Audit
 */
trait CreatedModifiedTrait
{
    use CreatedTrait;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", name="modified_at", nullable=true)
     * @Serialize\Expose
     * @Serialize\Type("DateTime<'d.m.Y H:i'>")
     */
    protected $modifiedAt = null;

    /**
     * Set modifiedAt to now
     *
     * @ORM\PreUpdate
     */
    public function setModifiedAtNow()
    {
        $this->modifiedAt = new \DateTime();
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     *
     * @return self
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTimeInterface|null
     */
    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modifiedAt;
    }

}