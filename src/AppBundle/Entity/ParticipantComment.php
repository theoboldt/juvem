<?php
namespace AppBundle\Entity;


use AppBundle\Entity\Audit\BlameableTrait;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use AppBundle\Entity\Participant;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CommentRepository")
 * @ORM\Table(name="participant_comment")
 * @ORM\HasLifecycleCallbacks()
 */
class ParticipantComment extends CommentBase
{
	use CreatedModifiedTrait, SoftDeleteTrait, BlameableTrait;

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
}
