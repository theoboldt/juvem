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

use DateTimeInterface;

class DateCustomFieldValue implements CustomFieldValueInterface
{
    const TYPE = 'date';

    const KEY_VALUE = 'value';

    /**
     * @var \DateTimeInterface|null
     */
    private ?DateTimeInterface $value = null;

    /**
     * Hydrate from array
     *
     * @param array $data JSON serialized data
     * @return DateCustomFieldValue
     */
    public static function createFromArray(array $data): DateCustomFieldValue
    {
        $rawValue = $data[self::KEY_VALUE] ?? null;
        if ($rawValue !== null & !$rawValue instanceof \DateTimeInterface) {
            try {
                $rawValue = new \DateTime($rawValue);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to convert "' . $rawValue . '" to datetime', 0, $e);
            }
        }

        return new self(
            $rawValue
        );
    }

    /**
     * Construct
     *
     * @param \DateTimeInterface|null $value Date value or null
     */
    public function __construct(
        ?\DateTimeInterface $value = null
    ) {
        $this->setValue($value);
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getValue(): ?\DateTimeInterface
    {
        return $this->value;
    }

    /**
     * @param DateTimeInterface|null $value
     * @return void
     */
    public function setValue(?\DateTimeInterface $value): void
    {
        if ($value) {
            $value = clone $value;
            if ($value instanceof \DateTime) {
                $value->setTime(10, 0, 0);
            } elseif ($value instanceof \DateTimeImmutable) {
                $value = $value->setTime(10, 0, 0);
            }
        }
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
        if ($this->value instanceof \DateTimeInterface) {
            return $this->value->format('d.m.Y');
        }
        return '';
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
            self::KEY_VALUE => $this->value->format('Y-m-d'),
        ];
    }

    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'date';
    }

    /**
     * {@inheritDoc}
     */
    public function getFormValue(): ?\DateTimeInterface
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormValue($value): void
    {
        $this->setValue($value);
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
            && $this->getTextualValue() === $other->getTextualValue()
        );
    }
}
