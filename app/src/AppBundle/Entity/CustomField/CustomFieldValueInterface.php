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

interface CustomFieldValueInterface
{

    /**
     * Get type of this value
     *
     * @return string
     */
    public static function getType(): string;

    /**
     * Hydrate from array
     *
     * @param array $data JSON serialized data
     * @return CustomFieldValueInterface
     */
    public static function createFromArray(
        array $data
    ): CustomFieldValueInterface;

    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @return string
     * @deprecated
     */
    public function getTextualValue(): string;

    /**
     * Get value prepared for form depending on type
     *
     * Get value prepared for form depending on type. Most of the time will provide some scalar,
     * sometimes the object itself if the form element used for this type is a special
     * one like {@see \AppBundle\Form\BankAccountType}
     *
     * @return int|string|null|self
     */
    public function getFormValue();

    /**
     * Set value from form depending on type
     *
     * Set value prepared for form depending on type. Most of the time will provide some scalar,
     * sometimes the object itself if the form element used for this type is a special
     * one like {@see \AppBundle\Form\BankAccountType}
     *
     * @param $value int|string|null|self
     * @return void
     */
    public function setFormValue($value): void;

    
    /**
     * Determine if this value is equal to transmitted one
     *
     * @param CustomFieldValueInterface $other
     * @return bool
     */
    public function isEqualTo(CustomFieldValueInterface $other): bool;
    
    /**
     * Implement \JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize(): array;

}
