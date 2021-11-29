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

use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use Doctrine\ORM\Mapping as ORM;
use Faker\Provider\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="feedback_questionnaire_fillout")
 */
class FeedbackQuestionnaireFillout implements ProvidesModifiedInterface, ProvidesCreatedInterface
{
    use CreatedModifiedTrait;

    /**
     * @ORM\Column(type="string", length=128)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @Assert\Uuid()
     */
    private ?string $uuid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event", 
     *     inversedBy="feedbackQuestionnaireFillouts", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade", nullable=false)
     */
    protected Event $event;

    /**
     * Questionnaire answers to different questions
     *
     * @ORM\Column(type="json_array", length=16777215, name="feedback_questionnaire", nullable=true)
     *
     * @var array|null
     */
    private $answers = [];

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private string $additions = '';

    /**
     * @param string|null $uuid
     * @param Event       $event
     * @param array|null  $answers
     * @param string      $additions
     */
    public function __construct(string $uuid = null, Event $event, ?array $answers = [], string $additions = '')
    {
        $this->uuid      = $uuid ?: Uuid::uuid();
        $this->event     = $event;
        $this->answers   = $answers;
        $this->additions = $additions;
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
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
     * @return array|null
     */
    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    /**
     * @param array|null $answers
     */
    public function setAnswers(?array $answers): void
    {
        $this->answers = $answers;
    }

    /**
     * @return string
     */
    public function getAdditions(): string
    {
        return $this->additions;
    }

    /**
     * @param string $additions
     */
    public function setAdditions(string $additions): void
    {
        $this->additions = $additions;
    }
    

}
