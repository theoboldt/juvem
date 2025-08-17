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
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;

trait CustomFieldValueTrait
{

    /**
     * Custom field values
     * 
     * @var array|null|CustomFieldValueCollection
     */
    private $customFieldValues = [];

    /**
     * Get custom field value collection
     *
     * @return CustomFieldValueCollection
     */
    public function getCustomFieldValues(): CustomFieldValueCollection
    {
        if (!$this->customFieldValues instanceof CustomFieldValueCollection) {
            $this->customFieldValues = CustomFieldValueCollection::createFromArray($this->customFieldValues);
        }
        return $this->customFieldValues;
    }

    /**
     * @param CustomFieldValueCollection|array|null $customFieldValues
     */
    public function setCustomFieldValues($customFieldValues): void
    {
        if (!$customFieldValues instanceof CustomFieldValueCollection 
            && $customFieldValues !== null
            && !is_array($customFieldValues) 
        ) {
            throw new \InvalidArgumentException('Unexpected type');
        }
        $this->customFieldValues = $customFieldValues;
    }

    /**
     * Get current entities custom fields
     *
     * @return Attribute[]
     */
    private function getCustomFields(): array
    {
        /** @var Event $event */
        $event = $this->getEvent();
        if (!$event) {
            throw new \InvalidArgumentException('Can find assigned custom fields if no related event is configured');
        }
        if ($this instanceof Participation) {
            $customFields = $event->getAcquisitionAttributes(true, false, false, true, true);
        } elseif ($this instanceof Participant) {
            $customFields = $event->getAcquisitionAttributes(false, true, false, true, true);
        } elseif ($this instanceof Employee) {
            $customFields = $event->getAcquisitionAttributes(false, false, true, true, true);
        } else {
            throw new \InvalidArgumentException(
                'This custom field trait is used at unknown class'
            );
        }
        return $customFields;
    }

    /**
     * Get custom field related to this entity by id
     *
     * @param int $bid
     * @return Attribute
     */
    private function getCustomFieldById(int $bid): Attribute
    {
        foreach ($this->getCustomFields() as $customField) {
            if ((int)$customField->getBid() === (int)$bid) {
                return $customField;
            }
        }

        throw new \OutOfBoundsException(
            'Requested custom field with id ' . $bid . ' was not found'
        );
    }

    /**
     * Getter for acquisition attribute mapping
     *
     * @param string $key Key containing name of fillout attribute
     * @return CustomFieldValueContainer
     */
    public function __get($key)
    {
        if (preg_match('/custom_field_(\d+)/', $key, $bidData)) {
            return $this->getCustomFieldValues()->get($bidData[1], false);
        }
        throw new \InvalidArgumentException(sprintf('Unknown property "%s" accessed', $key));
    }

    /**
     * Getter for acquisition attribute mapping
     *
     * @param string $key   Key containing name of fillout attribute
     * @param mixed  $value New value for this fillout
     */
    public function __set($key, $value)
    {
        if (preg_match('/custom_field_(\d+)/', $key, $bidData)) {
            $value2 = $this->getCustomFieldValues()->get($bidData[1], true);
            $value2->setValue($value);
        }
    }
}
