<?php
namespace AppBundle\Entity;

use AppBundle\Entity\Audit\BlameableTrait;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CommentBase
 *
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 *
 * @package AppBundle\Entity
 */
abstract class CommentBase
{
	use CreatedModifiedTrait, SoftDeleteTrait, BlameableTrait;

    /**
     * @ORM\Column(type="integer", name="cid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $cid;

    /**
     * @ORM\Column(type="text", name="content")
     * @Assert\NotBlank()
     */
    protected $content;

    /**
     * Get cid
     *
     * @return integer
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return CommentBase
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
