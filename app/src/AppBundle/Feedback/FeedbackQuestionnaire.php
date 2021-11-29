<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Feedback;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

class FeedbackQuestionnaire implements \JsonSerializable
{

    /**
     * @var string
     */
    private string $uuid;

    /**
     * @Assert\Type("string")
     * @var string
     */
    private string $introductionEmail = '';

    /**
     * @Assert\Type("string")
     * @var string
     */
    private string $introductionQuestionnaire = '';

    /**
     * Feedback questionnaire will be requested from participants specified amount of days after event
     *
     * Feedback questionnaire will be requested from participants specified amount of days after event. If set to
     * zero, no feedback questionnaire will be sent automatically
     *
     * @Assert\Range(
     *      min = 0,
     *      max = 99
     * )
     * @var int
     */
    protected $feedbackDaysAfterEvent = 0;

    /**
     * Listing of feedback questions
     *
     * @var ArrayCollection|FeedbackQuestionInterface[]
     */
    private $questions;

    /**
     * Last modification date of questionnaire
     *
     * @var \DateTimeImmutable|string
     */
    private $lastModified;

    /**
     * @param array $data
     * @return FeedbackQuestionnaire
     */
    public static function createFromArray(array $data): FeedbackQuestionnaire
    {
        $questions = $data['questions'] ?? [];
        foreach ($questions as &$question) {
            if (is_array($question)) {
                $question = FeedbackQuestion::createFromArray($question);
            }
            if (!$question instanceof FeedbackQuestionInterface) {
                throw new \InvalidArgumentException('Question must implement ' . FeedbackQuestionInterface::class);
            }
        } //foreach
        unset($question);

        $lastModified = null;
        if (isset($data['lastModified'])) {
            $lastModified = is_string($data['lastModified'])
                ? \DateTimeImmutable::createFromFormat(\DateTime::ISO8601, $data['lastModified'])
                : $data['lastModified'];
        }
        return new self(
            $data['introductionEmail'] ?? '',
            $data['introductionQuestionnaire'] ?? '',
            $data['feedbackDaysAfterEvent'] ?? 0,
            $questions,
            $lastModified,
            $data['uuid'] ?? '',
        );
    }

    /**
     * @param string                      $introductionEmail
     * @param string                      $introductionQuestionnaire
     * @param int                         $feedbackDaysAfterEvent
     * @param FeedbackQuestionInterface[] $questions
     * @param \DateTimeImmutable|null     $lastModified
     * @param string|null                 $uuid
     */
    public function __construct(
        string              $introductionEmail,
        string              $introductionQuestionnaire,
        int                 $feedbackDaysAfterEvent = 0,
        array               $questions = [],
        ?\DateTimeImmutable $lastModified = null,
        ?string             $uuid = null
    ) {
        $this->introductionEmail         = $introductionEmail;
        $this->introductionQuestionnaire = $introductionQuestionnaire;
        $this->feedbackDaysAfterEvent    = $feedbackDaysAfterEvent;
        $this->questions                 = new ArrayCollection($questions);
        $this->lastModified              = $lastModified ?: new \DateTimeImmutable();
        $this->uuid                      = $uuid ?: \Faker\Provider\Uuid::uuid();
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getIntroductionEmail(): string
    {
        return $this->introductionEmail;
    }

    /**
     * @return string
     */
    public function getIntroductionQuestionnaire(): string
    {
        return $this->introductionQuestionnaire;
    }

    /**
     * @return int
     */
    public function getFeedbackDaysAfterEvent(): int
    {
        return $this->feedbackDaysAfterEvent;
    }

    /**
     * @return FeedbackQuestionInterface[]
     */
    public function getQuestions(): array
    {
        return $this->questions->toArray();
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getLastModified(): \DateTimeImmutable
    {
        if (is_string($this->lastModified)) {
            $this->lastModified = \DateTimeImmutable::createFromFormat(\DateTime::ISO8601, $this->lastModified);
        }
        return $this->lastModified;
    }

    /**
     * @param string $introductionEmail
     */
    public function setIntroductionEmail(?string $introductionEmail = ''): void
    {
        $this->introductionEmail = (string)$introductionEmail;
    }

    /**
     * @param string $introductionQuestionnaire
     */
    public function setIntroductionQuestionnaire(?string $introductionQuestionnaire = ''): void
    {
        $this->introductionQuestionnaire = (string)$introductionQuestionnaire;
    }

    /**
     * @param int $feedbackDaysAfterEvent
     */
    public function setFeedbackDaysAfterEvent(int $feedbackDaysAfterEvent = 0): void
    {
        $this->feedbackDaysAfterEvent = $feedbackDaysAfterEvent;
    }

    /**
     * @param FeedbackQuestionInterface[]|ArrayCollection $questions
     */
    public function setQuestions($questions): void
    {
        $this->questions = $questions;
    }
    
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $questions = [];

        foreach ($this->questions as $question) {
            $questions[] = $question->jsonSerialize();
        }

        return [
            'introductionEmail'         => $this->introductionEmail,
            'introductionQuestionnaire' => $this->introductionQuestionnaire,
            'feedbackDaysAfterEvent'    => $this->feedbackDaysAfterEvent,
            'questions'                 => $questions,
            'lastModified'              => $this->lastModified->format(\DateTime::ISO8601),
            'uuid'                      => $this->uuid,
        ];
    }
}
