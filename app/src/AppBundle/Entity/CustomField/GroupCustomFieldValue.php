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

class GroupCustomFieldValue implements CustomFieldValueInterface, OptionProvidingCustomFieldValueInterface, \JsonSerializable
{
    const TYPE = 'group';

    const KEY_VALUE = 'value';

    /**
     * @var int|null
     */
    private ?int $value = null;

    /**
     * Hydrate from array
     *
     * @param array $data JSON serialized data
     * @return GroupCustomFieldValue
     */
    public static function createFromArray(array $data): GroupCustomFieldValue
    {
        return new self(
            $data[self::KEY_VALUE] ?? null
        );
    }

    /**
     * Construct
     *
     * @param int|null $value Group id
     */
    public function __construct(
        ?int $value = null
    ) {
        $this->value = $value;
    }

    /**
     * @return int|null
     */
    public function getValue(): ?int
    {
        return $this->value;
    }

    /**
     * @param int|null $value
     * @return void
     */
    public function setValue(?int $value): void
    {
        $this->value = $value;
    }

    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @return string
     * @deprecated
     */
    public function getTextualValue(): string
    {
        return (string)$this->getValue();
    }

    /**
     * Textual value
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTextualValue();
    }

    /**
     * Implement \JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            self::KEY_VALUE => $this->value,
        ];
    }

    /**
     * @return string
     */
    public static function getType(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormValue(): ?array
    {
        if ($this->value) {
            return [$this->value];
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormValue($value): void
    {
        if (is_array($value)) {
            if (count($value)) {
                $value = reset($value);
            } else {
                $value = null;
            }
        }
        $this->value = $value;
    }

    /**
     * @return int[]
     */
    public function getSelectedChoices(): array
    {
        return $this->value ? [$this->value] : []; 
    }

    /**
     * Determine if this value is equal to transmitted one
     *
     * @param CustomFieldValueInterface $other
     * @return bool
     */
    public function isEqualTo(CustomFieldValueInterface $other): bool
    {
        return (
            $other instanceof self
            && $this->getValue() === $other->getValue()
        );
    }
}
