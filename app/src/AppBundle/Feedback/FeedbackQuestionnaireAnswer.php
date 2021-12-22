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

class FeedbackQuestionnaireAnswer implements \JsonSerializable
{

    /**
     * Name/ID of question
     *
     * @var string
     */
    private string $name;

    /**
     * Chosen answer
     *
     * @var int|null
     */
    private ?int $answer;

    /**
     * @param string   $name
     * @param int|null $answer
     */
    public function __construct(string $name, ?int $answer)
    {
        $this->name   = $name;
        $this->answer = $answer;
    }

    /**
     * Create instance from array
     *
     * @param array $data
     * @return FeedbackQuestionnaireAnswer
     */
    public static function createFromArray(array $data): FeedbackQuestionnaireAnswer
    {
        return new self($data['name'], $data['answer'] ?? null);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return int|null
     */
    public function getAnswer(): ?int
    {
        return $this->answer;
    }

    /**
     * @param int|null $answer
     */
    public function setAnswer(?int $answer): void
    {
        $this->answer = $answer;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'name'   => $this->name,
            'answer' => $this->answer,
        ];
    }

    public function __toString()
    {
        return (string)$this->answer;
    }
}
