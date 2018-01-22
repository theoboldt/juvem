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


use AppBundle\Entity\Audit\CreatorModifierTrait;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ParticipationCommentRepository")
 * @ORM\Table(name="participation_comment")
 * @ORM\HasLifecycleCallbacks()
 */
class ParticipationComment extends CommentBase
{
	use CreatedModifiedTrait, SoftDeleteTrait, CreatorModifierTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="comments")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade")
     */
    protected $participation;

    /**
     * Set participation
     *
     * @param Participation $participation
     *
     * @return ParticipationComment
     */
    public function setParticipation(Participation $participation = null)
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation
     *
     * @return Participation
     */
    public function getParticipation()
    {
        return $this->participation;
    }

    /**
     * Get related objects id
     *
     * @return string
     */
    public function getRelatedId()
    {
        return $this->getParticipation()->getPid();
    }
}
