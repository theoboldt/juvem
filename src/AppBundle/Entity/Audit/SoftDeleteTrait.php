<?php
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
     * @param \DateTime $deletedAt
     *
     * @return self
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }



}