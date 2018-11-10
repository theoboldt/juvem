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


use Doctrine\Common\Collections\Collection;

trait CommentableTrait
{

    /**
     * Contains the comments assigned
     *
     * @var Collection|CommentBase[]
     */
    protected $comments;

    /**
     * Add comment
     *
     * @param CommentBase $comment
     *
     * @return self
     */
    public function addComment(CommentBase $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove a comment
     *
     * @param CommentBase $comment
     */
    public function removeComment(CommentBase $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getComments()
    {
        return $this->comments;
    }
}
