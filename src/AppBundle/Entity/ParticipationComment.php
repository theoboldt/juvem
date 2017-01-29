<?php
namespace AppBundle\Entity;


use AppBundle\Entity\Audit\BlameableTrait;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CommentRepository")
 * @ORM\Table(name="participation_comment")
 * @ORM\HasLifecycleCallbacks()
 */
class ParticipationComment extends CommentBase
{
	use CreatedModifiedTrait, SoftDeleteTrait, BlameableTrait;

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
