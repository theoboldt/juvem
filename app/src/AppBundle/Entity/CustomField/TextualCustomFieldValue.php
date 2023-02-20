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

class TextualCustomFieldValue implements CustomFieldValueInterface
{
    const TYPE = 'text';

    const KEY_VALUE = 'value';

    /**
     * @var string|null
     */
    private ?string $value = null;

    /**
     * Hydrate from array
     *
     * @param array $data JSON serialized data
     * @return TextualCustomFieldValue
     */
    public static function createFromArray(array $data): TextualCustomFieldValue
    {
        return new self(
            $data[self::KEY_VALUE] ?? null
        );
    }

    /**
     * Construct
     *
     * @param string|null $value Textual value or null
     */
    public function __construct(
        string $value = null
    ) {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     * @return void
     */
    public function setValue(?string $value): void
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
        return 'text';
    }

    /**
     * {@inheritDoc}
     */
    public function getFormValue(): ?string
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormValue($value): void
    {
        $this->value = $value;
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
