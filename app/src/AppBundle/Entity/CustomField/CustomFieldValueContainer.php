<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\CustomField;

class CustomFieldValueContainer implements \JsonSerializable
{
    const KEY_CUSTOM_FIELD_ID = 'bid';

    const KEY_TYPE = 'type';

    const KEY_COMMENT = 'comment';

    /**
     * ID of custom field
     *
     * @var int
     */
    private int $customFieldId;

    /**
     * Comment if configured for this value
     *
     * @var string|null
     */
    private ?string $comment;

    /**
     * @var string
     */
    private string $type;

    /**
     * Type specific value container
     *
     * @var CustomFieldValueInterface|null
     */
    private ?CustomFieldValueInterface $value;

    /**
     * Hydrate from array
     *
     * @param array $data JSON serialized data
     * @return CustomFieldValueContainer
     */
    public static function createFromArray(array $data): CustomFieldValueContainer
    {
        if (!isset($data[self::KEY_CUSTOM_FIELD_ID])) {
            throw new InsufficientDataForCustomFieldValueTypeException('Data does not provide custom field id');
        }
        if (!isset($data[self::KEY_TYPE])) {
            throw new InsufficientDataForCustomFieldValueTypeException('Data does not provide custom type');
        }

        $item = new self(
            $data[self::KEY_CUSTOM_FIELD_ID],
            $data[self::KEY_TYPE],
            $data[self::KEY_COMMENT] ?? null,
            null
        );

        switch ($data[self::KEY_TYPE]) {
            case TextualCustomFieldValue::getType():
                $value = TextualCustomFieldValue::createFromArray($data);
                break;
            case ChoiceCustomFieldValue::getType():
                $value = ChoiceCustomFieldValue::createFromArray($data);
                break;
            case GroupCustomFieldValue::getType():
                $value = GroupCustomFieldValue::createFromArray($data);
                break;
            case ParticipantDetectingCustomFieldValue::getType():
                $value = ParticipantDetectingCustomFieldValue::createFromArray($data);
                break;
            case NumberCustomFieldValue::getType():
                $value = NumberCustomFieldValue::createFromArray($data);
                break;
            case BankAccountCustomFieldValue::getType():
                $value = BankAccountCustomFieldValue::createFromArray($data);
                break;
            case DateCustomFieldValue::getType();
                $value = DateCustomFieldValue::createFromArray($data);
                break;
            //todo types
            default:
                throw new \InvalidArgumentException('Unknown type ' . $data[self::KEY_TYPE] . ' occurred');
                break;
        }

        $item->setValue($value);
        return $item;
    }

    /**
     * Construct
     *
     * @param int                            $customFieldId Related custom field id
     * @param string                         $type          Custom field type
     * @param string|null                    $comment       Comment if configured for this value
     * @param CustomFieldValueInterface|null $value         Specific value type
     */
    public function __construct(
        int                        $customFieldId,
        string                     $type,
        string                     $comment = null,
        ?CustomFieldValueInterface $value = null
    ) {
        if ($value === null) {
            switch ($type) {
                case TextualCustomFieldValue::getType():
                    $value = new TextualCustomFieldValue();
                    break;
                case ChoiceCustomFieldValue::getType():
                    $value = new ChoiceCustomFieldValue();
                    break;
                case GroupCustomFieldValue::getType():
                    $value = new GroupCustomFieldValue();
                    break;
                case ParticipantDetectingCustomFieldValue::getType():
                    $value = new ParticipantDetectingCustomFieldValue();
                    break;
                case NumberCustomFieldValue::getType():
                    $value = new NumberCustomFieldValue();
                    break;
                case BankAccountCustomFieldValue::getType():
                    $value = new BankAccountCustomFieldValue();
                    break;
                case DateCustomFieldValue::getType();
                    $value = new DateCustomFieldValue();
                    break;
                default:
                    //todo types
                    throw new \InvalidArgumentException('Unknown type ' . $type . ' occurred');
                    break;
            }
        }

        $this->customFieldId = $customFieldId;
        $this->type          = $type;
        $this->comment       = $comment;
        $this->value         = $value;
    }

    /**
     * Set value
     *
     * @param CustomFieldValueInterface $value
     */
    public function setValue(CustomFieldValueInterface $value): void
    {
        $this->value = $value;
    }

    /**
     * @return CustomFieldValueInterface
     */
    public function getValue(): CustomFieldValueInterface
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getCustomFieldId(): int
    {
        return $this->customFieldId;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Determine if comment is set
     *
     * @return bool
     */
    public function hasComment(): bool
    {
        return !empty($this->comment);
    }

    /**
     * Get field type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string|null $comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * Determine if this value container is equal to transmitted one
     *
     * @param CustomFieldValueContainer $other
     * @return bool
     */
    public function isEqualTo(CustomFieldValueContainer $other): bool
    {
        return (
            $this->getCustomFieldId() === $other->getCustomFieldId()
            && $this->getType() === $other->getType()
            && $this->getComment() === $other->getComment()
            && $this->getValue()->isEqualTo($other->getValue())
        );
    }

    /**
     * Implement \JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            [
                self::KEY_CUSTOM_FIELD_ID => $this->customFieldId,
                self::KEY_TYPE            => $this->getValue()::getType(),
                self::KEY_COMMENT         => $this->comment,
            ],
            $this->getValue()->jsonSerialize()
        );
    }
}
