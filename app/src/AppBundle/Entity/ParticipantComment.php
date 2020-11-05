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
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesCreatorInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\ProvidesModifierInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ParticipantCommentRepository")
 * @ORM\Table(name="participant_comment")
 * @ORM\HasLifecycleCallbacks()
 */
class ParticipantComment extends CommentBase implements ProvidesModifiedInterface, ProvidesCreatedInterface, ProvidesCreatorInterface, ProvidesModifierInterface
{
	use CreatedModifiedTrait, SoftDeleteTrait, CreatorModifierTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Participant", inversedBy="comments")
     * @ORM\JoinColumn(name="aid", referencedColumnName="aid", onDelete="cascade")
     */
    protected $participant;

    /**
     * Set participant
     *
     * @param Participant $participant
     *
     * @return ParticipantComment
     */
    public function setParticipant(Participant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get participant
     *
     * @return Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Get related objects id
     *
     * @return string
     */
    public function getRelatedId()
    {
        return $this->getParticipant()->getAid();
    }
}
