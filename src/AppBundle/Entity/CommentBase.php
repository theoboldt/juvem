<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     * Get class name
     *
     * @return string
     */
    public function getBaseClassName()
    {
        return get_class($this);
    }

    /**
     * Get id of related object
     *
     * @return integer
     */
    public abstract function getRelatedId();

    /**
     * Get related object
     *
     * @return string
     */
    public function getRelated()
    {
        if (preg_match('/(?:\\\\)*([^\\\\]+)Comment$/', $this->getBaseClassName(), $classData)) {
            $relatedAcessor = 'get'.$classData[1];
            $comment      = new \ReflectionClass(get_class($this));
            if ($comment->hasMethod($relatedAcessor)) {
                return $this->$relatedAcessor();
            } else {
                throw new \InvalidArgumentException('Comment class has not expected accessor for related object');
            }
        } else {
            throw new \InvalidArgumentException('Comment class name is not as expected');
        }
    }
}
