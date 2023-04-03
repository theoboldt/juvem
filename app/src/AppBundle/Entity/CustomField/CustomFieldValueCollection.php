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

use AppBundle\Entity\AcquisitionAttribute\Attribute;

class CustomFieldValueCollection implements \JsonSerializable, \IteratorAggregate//, Collection, Selectable
{
    /**
     * An array containing the entries of this collection.
     *
     * @var CustomFieldValueContainer[]
     */
    private array $elements = [];


    /**
     * Create collection from array
     *
     * @param array[]|null $data List of json encoded {@see CustomFieldValueContainer}
     * @return CustomFieldValueCollection
     */
    public static function createFromArray(?array $data): CustomFieldValueCollection
    {
        $customFieldCollectionValues = [];
        if (is_array($data)) {
            foreach ($data as $customFieldValueData) {
                $customFieldCollectionValues[] = CustomFieldValueContainer::createFromArray(
                    $customFieldValueData
                );
            }
        }
        return new CustomFieldValueCollection($customFieldCollectionValues);
    }

    /**
     * @param CustomFieldValueContainer[] $elements
     */
    public function __construct(array $elements = [])
    {
        foreach ($elements as $element) {
            if (!$element instanceof CustomFieldValueContainer) {
                throw new \InvalidArgumentException('All elements must be ' . CustomFieldValueContainer::class);
            }
            $this->elements[$element->getCustomFieldId()] = $element;
        }
        ksort($this->elements);
    }

    /**
     * Get element from collection by id
     *
     * @param int  $id                 Id of element
     * @param bool $throwIfUnavailable If true, throws an exception if no such element exists
     * @return CustomFieldValueContainer|null
     */
    public function get(int $id, bool $throwIfUnavailable = false): ?CustomFieldValueContainer
    {
        if (isset($this->elements[$id])) {
            return $this->elements[$id];
        } elseif ($throwIfUnavailable) {
            throw new \OutOfBoundsException('Requested element ' . $id . ' unavailable');
        } else {
            return null;
        }
    }

    /**
     * Get element by custom field; Add it automatically to list if not yet exists
     *
     * @param Attribute $customField Custom Field
     * @return CustomFieldValueContainer Value container
     */
    public function getByCustomField(Attribute $customField): CustomFieldValueContainer
    {
        if (isset($this->elements[$customField->getBid()])) {
            return $this->elements[$customField->getBid()];
        } else {
            $element = new CustomFieldValueContainer($customField->getBid(), $customField->getCustomFieldValueType());
            $this->add($element);
            return $element;
        }
    }

    /**
     * Add element to collection
     *
     * @param CustomFieldValueContainer $element
     * @return void
     */
    public function add(CustomFieldValueContainer $element): void
    {
        $this->elements[$element->getCustomFieldId()] = $element;
        ksort($this->elements);
    }

    /**
     * Remove element from collection if present
     *
     * @param CustomFieldValueContainer $element
     * @return void
     */
    public function remove(CustomFieldValueContainer $element): void
    {
        if (isset($this->elements[$element->getCustomFieldId()])) {
            unset($this->elements[$element->getCustomFieldId()]);
        }
    }

    /**
     * Get json serializable representation of data
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $elements = [];
        foreach ($this->elements as $value) {
            $elements[$value->getCustomFieldId()] = $value->jsonSerialize();
        }

        return $elements;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->elements);
    }

    /**
     * Getter for custom Field value collection
     *
     * @param string $key Key containing name of custom field
     * @return CustomFieldValueContainer
     */
    public function __get($key)
    {
        if (preg_match('/custom_field_(\d+)/', $key, $customFieldData)) {
            return $this->get((int)$customFieldData[1]);
        } else {
            $a = 1;
        }
    }

    /**
     * Setter for custom Field value collection
     *
     * @param string $key   Key containing name of fillout attribute
     * @param mixed  $value New value for this fillout
     */
    public function __set($key, $value)
    {
        if (preg_match('/custom_field_(\d+)/', $key, $customFieldData)) {
            if (!$value instanceof CustomFieldValueContainer) {
                throw new \InvalidArgumentException('Value of unexpected class ' . get_class($value));
            }
            $this->elements[$customFieldData[1]] = $value;
            ksort($this->elements);
        } else {
            $a = 1;
        }
    }
}
