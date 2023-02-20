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

class ChoiceCustomFieldValue implements CustomFieldValueInterface, OptionProvidingCustomFieldValueInterface, \JsonSerializable
{
    const TYPE = 'choice';

    const KEY_VALUE = 'value';

    private array $selectedChoices;

    /**
     * Hydrate from array
     *
     * @param array $data JSON serialized data
     * @return ChoiceCustomFieldValue
     */
    public static function createFromArray(
        array $data
    ): ChoiceCustomFieldValue {
        return new self(
            $data[self::KEY_VALUE] ?? [],
        );
    }

    /**
     * Construct
     *
     * @param array|int[] $selectedChoices List of IDs of selected choices
     */
    public function __construct(
        array $selectedChoices = []
    ) {
        $this->selectedChoices = $selectedChoices;
    }

    /**
     * @return array
     */
    public function getSelectedChoices(): array
    {
        return $this->selectedChoices;
    }

    /**
     * @param array $selectedChoices
     * @return void
     */
    public function setSelectedChoices(array $selectedChoices): void
    {
        $this->selectedChoices = $selectedChoices;
    }

    /**
     * @return int[]
     */
    public function getValue(): array
    {
        return $this->getSelectedChoices();
    }


    /**
     * @param array $selectedChoices
     * @return void
     */
    public function setValue(array $selectedChoices): void
    {
        $this->setSelectedChoices($selectedChoices);
    }


    /**
     * Determine if anything is checked
     *
     * @return bool
     */
    public function hasSomethingSelected(): bool
    {
        return count($this->selectedChoices) > 0;
    }

    /**
     * Determine if this selection has multiple items checked
     *
     * @return bool
     */
    public function hasMultipleSelectedChoices(): bool
    {
        return count($this->selectedChoices) > 1;
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
        if (count($this->selectedChoices)) {
            return '[' . implode('], [', $this->selectedChoices) . ']';
        } else {
            return '';
        }
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
            self::KEY_VALUE => $this->selectedChoices,
        ];
    }

    /**
     * Get type of this value
     *
     * @return string
     */
    public static function getType(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormValue(): array
    {
        return $this->getSelectedChoices();
    }

    /**
     * {@inheritDoc}
     */
    public function setFormValue($value): void
    {
        if (is_scalar($value)) {
            $value = [$value];
        }
        $this->selectedChoices = $value;
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
            && array_values($this->getSelectedChoices()) === array_values($other->getSelectedChoices())
        );
    }
}
