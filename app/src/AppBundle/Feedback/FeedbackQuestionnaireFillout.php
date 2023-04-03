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


class FeedbackQuestionnaireFillout implements \JsonSerializable
{
    /**
     * @var FeedbackQuestionnaireAnswer[]
     */
    private array $answers;

    /**
     * Fillout comment
     *
     * @var string
     */
    private string $comment = '';

    /**
     * Create instance from array
     *
     * @param array $data
     * @return FeedbackQuestionnaireFillout
     */
    public static function createFromArray(array $data): FeedbackQuestionnaireFillout
    {
        $answers = [];
        if (isset($data['answers'])) {
            foreach ($data['answers'] as $answer) {
                $answers[] = FeedbackQuestionnaireAnswer::createFromArray($answer);
            }
        }

        return new self($answers, $data['comment'] ?? '');
    }

    /***
     * Validate question identifier
     *
     * @param string $identifier Transmitted identifier
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function validateAnswerIdentifier(string $identifier): void
    {
        if (!preg_match('/question-([A-Fa-f0-9-]+)-(thesis|counter-thesis)/', $identifier)) {
            throw new \InvalidArgumentException(sprintf('Unknown question "%s" accessed', $identifier));
        }

    }

    /**
     * @param FeedbackQuestionnaireAnswer[] $answers
     * @param string                        $comment
     */
    public function __construct(array $answers = [], string $comment = '')
    {
        $this->answers = $answers;
        $this->comment = $comment;
    }

    /**
     * @return FeedbackQuestionnaireAnswer[]
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @param FeedbackQuestionnaireAnswer[] $answers
     */
    public function setAnswers(array $answers): void
    {
        $this->answers = $answers;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = (string)$comment;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $answers = [];
        /** @var FeedbackQuestionnaireAnswer $answer */
        foreach ($this->answers as $answer) {
            $answers[] = $answer->jsonSerialize();
        }

        return [
            'answers' => $answers,
            'comment' => $this->comment,
        ];
    }

    /**
     * Access answer with transmitted name
     *
     * @param string $name                        Name of question
     * @param bool   $createInstanceIfNotExisting If true, answer is automatically created if not yet available
     * @return FeedbackQuestionnaireAnswer|null
     */
    public function getAnswer(string $name, bool $createInstanceIfNotExisting = false): ?FeedbackQuestionnaireAnswer
    {
        /** @var FeedbackQuestionnaireAnswer $answer */
        foreach ($this->answers as $answer) {
            if ($answer->getName() === $name) {
                return $answer;
            }
        }
        if ($createInstanceIfNotExisting) {
            $answer          = new FeedbackQuestionnaireAnswer($name, null);
            $this->answers[] = $answer;
            return $answer;
        }

        return null;
    }

    /**
     * Getter for answer
     *
     * @param string $key Key containing name of answer
     * @return FeedbackQuestionnaireAnswer
     */
    public function __get($key)
    {
        self::validateAnswerIdentifier($key);
        return $this->getAnswer($key, true);
    }

    /**
     * Setter for answer
     *
     * @param string $key   Key containing name of answer
     * @param mixed  $value New value
     */
    public function __set($key, $value)
    {
        self::validateAnswerIdentifier($key);
        $answer = $this->getAnswer($key, true);
        $answer->setAnswer($value);
    }

}
