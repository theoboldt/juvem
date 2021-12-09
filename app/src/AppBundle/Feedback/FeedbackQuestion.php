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

use Symfony\Component\Validator\Constraints as Assert;

class FeedbackQuestion implements \JsonSerializable
{
    const TYPE_AGREEMENT = 1;

    const INTERPRETATION_NEGATIVE       = 0;
    const INTERPRETATION_NEGATIVE_LABEL = 'negativ';
    const INTERPRETATION_NEUTRAL        = 1;
    const INTERPRETATION_NEUTRAL_LABEL  = 'neutral';
    const INTERPRETATION_POSITIVE       = 2;
    const INTERPRETATION_POSITIVE_LABEL = 'positiv';

    /**
     * @var string
     */
    private string $uuid;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @var string
     */
    private string $topic;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @var string
     */
    private string $thesis;

    /**
     * @Assert\Type("string")
     * @var string
     */
    private string $counterThesis = '';

    /**
     * @Assert\Type("int")
     * @var int
     */
    private int $questionType = self::TYPE_AGREEMENT;

    /**
     * @Assert\Type("int")
     * @var int
     */
    private int $interpretation = self::INTERPRETATION_NEUTRAL;


    /**
     * Create from array
     *
     * @param array $data
     * @return FeedbackQuestion
     */
    public static function createFromArray(array $data): FeedbackQuestion
    {
        return new self(
            $data['topic'] ?? '',
            $data['thesis'] ?? '',
            $data['counterThesis'] ?? '',
            $data['questionType'] ?? self::TYPE_AGREEMENT,
            $data['interpretation'] ?? self::INTERPRETATION_NEUTRAL,
            $data['uuid'],
        );
    }

    /**
     * @param int         $questionType
     * @param string|null $uuid
     */
    public function __construct(
        string  $topic,
        string  $thesis,
        string  $counterThesis = '',
        int     $questionType = self::TYPE_AGREEMENT,
        int     $interpretation = self::INTERPRETATION_NEUTRAL,
        ?string $uuid = null
    ) {
        $this->topic          = $topic;
        $this->thesis         = $thesis;
        $this->counterThesis  = $counterThesis;
        $this->questionType   = $questionType;
        $this->interpretation = $interpretation;
        $this->uuid           = $uuid === null ? \Faker\Provider\Uuid::uuid() : $uuid;
    }


    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'uuid'           => $this->uuid,
            'topic'          => $this->topic,
            'thesis'         => $this->thesis,
            'counterThesis'  => $this->counterThesis,
            'questionType'   => $this->questionType,
            'interpretation' => $this->interpretation,
        ];
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
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     */
    public function setTopic(string $topic): void
    {
        $this->topic = $topic;
    }

    /**
     * @return string
     */
    public function getThesis(): string
    {
        return $this->thesis;
    }

    /**
     * @param string $thesis
     */
    public function setThesis(string $thesis): void
    {
        $this->thesis = $thesis;
    }

    /**
     * @return bool
     */
    public function hasCounterThesis(): bool
    {
        return !empty($this->counterThesis);
    }

    /**
     * @return string
     */
    public function getCounterThesis(): string
    {
        return $this->counterThesis;
    }

    /**
     * @param string $counterThesis
     */
    public function setCounterThesis(string $counterThesis): void
    {
        $this->counterThesis = $counterThesis;
    }

    /**
     * @return int
     */
    public function getQuestionType(): int
    {
        return $this->questionType;
    }

    /**
     * @return int
     */
    public function getInterpretation(): int
    {
        return $this->interpretation;
    }

    /**
     * @param int $interpretation
     */
    public function setInterpretation(int $interpretation): void
    {
        $this->interpretation = $interpretation;
    }

}
