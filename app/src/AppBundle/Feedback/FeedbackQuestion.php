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

class FeedbackQuestion implements \JsonSerializable, FeedbackQuestionInterface
{
    const TYPE_AGREEMENT = 1;

    /**
     * @var string
     */
    private string $uuid;

    /**
     * @var string
     */
    private string $internalTitle;

    /**
     * @var string
     */
    private string $questionText;

    /**
     * @var int
     */
    private int $questionType = self::TYPE_AGREEMENT;


    /**
     * Create from array
     * 
     * @param array $data
     * @return FeedbackQuestion
     */
    public static function createFromArray(array $data): FeedbackQuestion
    {
        return new self(
            $data['internalTitle'],
            $data['questionText'],
            $data['questionType'],
            $data['uuid'],
        );
    }

    /**
     * @param string      $internalTitle
     * @param string      $questionText
     * @param int         $questionType
     * @param string|null $uuid
     */
    public function __construct(
        string  $internalTitle,
        string  $questionText,
        int     $questionType = self::TYPE_AGREEMENT,
        ?string $uuid = null
    ) {
        $this->internalTitle = $internalTitle;
        $this->questionText  = $questionText;
        $this->questionType  = $questionType;
        $this->uuid          = $uuid === null ? \Faker\Provider\Uuid::uuid() : $uuid;
    }


    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'uuid'          => $this->uuid,
            'internalTitle' => $this->internalTitle,
            'questionText'  => $this->questionText,
            'questionType'  => $this->questionType,
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
    public function getInternalTitle(): string
    {
        return $this->internalTitle;
    }

    /**
     * @return string
     */
    public function getQuestionText(): string
    {
        return $this->questionText;
    }

    /**
     * @return int
     */
    public function getQuestionType(): int
    {
        return $this->questionType;
    }
}
