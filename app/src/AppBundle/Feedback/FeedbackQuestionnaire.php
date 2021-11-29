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

class FeedbackQuestionnaire implements \JsonSerializable
{

    /**
     * @var string
     */
    private string $uuid;

    /**
     * @var string
     */
    private string $introductionEmail = '';

    /**
     * @var string
     */
    private string $introductionQuestionnaire = '';

    /**
     * Listing of feedback questions
     *
     * @var FeedbackQuestionInterface[]
     */
    private array $questions = [];

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
        $questions = $data['questions'];
        foreach ($questions as &$question) {
            if (is_array($question)) {
                $question = FeedbackQuestion::createFromArray($question);
            }
            if (!$question instanceof FeedbackQuestionInterface) {
                throw new \InvalidArgumentException('Question must implement ' . FeedbackQuestionInterface::class);
            }
        } //foreach
        unset($question);

        return new self(
            $data['introductionEmail'],
            $data['introductionQuestionnaire'],
            $questions,
            is_string($data['lastModified'])
                ? \DateTimeImmutable::createFromFormat(\DateTime::ISO8601, $data['lastModified'])
                : $data['lastModified'],
            $data['uuid'],
        );
    }

    /**
     * @param string                      $introductionEmail
     * @param string                      $introductionQuestionnaire
     * @param FeedbackQuestionInterface[] $questions
     * @param \DateTimeImmutable|null     $lastModified
     * @param string|null                 $uuid
     */
    public function __construct(
        string              $introductionEmail,
        string              $introductionQuestionnaire,
        array               $questions = [],
        ?\DateTimeImmutable $lastModified = null,
        ?string             $uuid = null
    ) {
        $this->introductionEmail         = $introductionEmail;
        $this->introductionQuestionnaire = $introductionQuestionnaire;
        $this->questions                 = $questions;
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
     * @return FeedbackQuestionInterface[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
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
            'questions'                 => $questions,
            'lastModified'              => $this->lastModified->format(\DateTime::ISO8601),
            'uuid'                      => $this->uuid,
        ];
    }
}
