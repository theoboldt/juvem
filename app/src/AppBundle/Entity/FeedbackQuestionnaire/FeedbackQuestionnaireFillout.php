<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\FeedbackQuestionnaire;

use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Event;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\FeedbackQuestionnaire\FeedbackQuestionnaireFilloutRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="feedback_questionnaire_fillout")
 */
class FeedbackQuestionnaireFillout implements ProvidesModifiedInterface, ProvidesCreatedInterface
{
    use CreatedModifiedTrait;

    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event",
     *     inversedBy="feedbackQuestionnaireFillouts", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade", nullable=false)
     */
    protected Event $event;

    /**
     * Questionnaire Fillout
     *
     * @ORM\Column(type="json_array", length=16777215, name="feedback_questionnaire", nullable=true)
     *
     * @var array|null
     */
    private $fillout = [];

    /**
     * @param Event                                                       $event
     * @param array|null|\AppBundle\Feedback\FeedbackQuestionnaireFillout $fillout
     */
    public function __construct(Event $event, ?array $fillout = [])
    {
        if ($fillout instanceof \AppBundle\Feedback\FeedbackQuestionnaireFillout) {
            $fillout = $fillout->jsonSerialize();
        }

        $this->event   = $event;
        $this->fillout = $fillout;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @param Event $event
     */
    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }

    /**
     * Provide fillout data
     *
     * @param bool $decoded If decoded is set to true, provide fillout entity
     * @return array|null|\AppBundle\Feedback\FeedbackQuestionnaireFillout
     */
    public function getFillout(bool $decoded = false)
    {
        if ($decoded) {
            if ($this->fillout) {
                return \AppBundle\Feedback\FeedbackQuestionnaireFillout::createFromArray($this->fillout);
            } else {
                return null;
            }
        }
        return $this->fillout;
    }

    /**
     * @param array|null|\AppBundle\Feedback\FeedbackQuestionnaireFillout $fillout
     */
    public function setFillout($fillout): void
    {
        if ($fillout instanceof \AppBundle\Feedback\FeedbackQuestionnaireFillout) {
            $fillout = $fillout->jsonSerialize();
        }
        $this->fillout = $fillout;
    }


}
