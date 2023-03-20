<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventListeners;

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\ChangeTracking\ScheduledEntityChange;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Entity\CustomField\BankAccountCustomFieldValue;
use AppBundle\Entity\CustomField\CustomFieldValueCollection;
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\DateCustomFieldValue;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\CustomField\NumberCustomFieldValue;
use AppBundle\Entity\CustomField\OptionProvidingCustomFieldValueInterface;
use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
use AppBundle\Entity\CustomField\TextualCustomFieldValue;
use Doctrine\Common\Collections\Collection;

class CustomFieldValueContainerChangeDetector
{

    private array $customFields = [];

    /**
     * @param array|null $customFields
     */
    public function __construct(?array $customFields = null)
    {
        if ($customFields !== null) {
            $this->customFields = $customFields;
        }
    }

    /**
     * Add custom fields from entity to cache
     *
     * @param EntityHavingCustomFieldValueInterface $entity Entity
     * @return void
     */
    private function collectCustomFields(EntityHavingCustomFieldValueInterface $entity)
    {
        $customFields = $entity->getEvent()->getAcquisitionAttributes();
        if ($customFields instanceof Collection) {
            $customFields = $customFields->toArray();
        }

        foreach ($customFields as $customField) {
            if (!isset($this->customFields[$customField->getBid()])) {
                $this->customFields[$customField->getBid()] = $customField;
            }
        }
    }

    /**
     * Get custom field by BID from cache if present
     *
     * @param int $bid
     * @return Attribute|null
     */
    private function getCustomField(int $bid): ?Attribute
    {
        return $this->customFields[$bid] ?? null;
    }

    /**
     * Detect changes for entity at custom field collection and add to change
     *
     * @param SupportsChangeTrackingInterface $entity
     * @param CustomFieldValueCollection      $comparableBefore
     * @param CustomFieldValueCollection      $comparableAfter
     * @param ScheduledEntityChange           $change
     * @return void
     */
    public function detect(
        SupportsChangeTrackingInterface $entity,
        CustomFieldValueCollection      $comparableBefore,
        CustomFieldValueCollection      $comparableAfter,
        ScheduledEntityChange           $change
    ): void {
        if (!$entity instanceof EntityHavingCustomFieldValueInterface) {
            throw new \RuntimeException('Entity must be of class ' . EntityHavingCustomFieldValueInterface::class);
        }
        $this->collectCustomFields($entity);

        /** @var CustomFieldValueContainer $customFieldValueContainerAfter */
        foreach ($comparableAfter->getIterator() as $customFieldValueContainerAfter) {
            $customField   = $this->getCustomField($customFieldValueContainerAfter->getCustomFieldId());
            $attributeName = $this->provideCustomFieldName($customFieldValueContainerAfter);

            $customFieldValueContainerBefore = $comparableBefore->get(
                $customFieldValueContainerAfter->getCustomFieldId()
            );

            if ($customFieldValueContainerBefore === null) {
                $change->addAttributeChange(
                    $attributeName,
                    '',
                    $this->getStorableRepresentation($customField, $customFieldValueContainerAfter)
                );
            } elseif (!$customFieldValueContainerAfter->isEqualTo($customFieldValueContainerBefore)) {
                $change->addAttributeChange(
                    $attributeName,
                    $this->getStorableRepresentation($customField, $customFieldValueContainerBefore),
                    $this->getStorableRepresentation($customField, $customFieldValueContainerAfter)
                );
            }
        }
        /** @var CustomFieldValueContainer $customFieldValueContainerBefore */
        foreach ($comparableBefore->getIterator() as $customFieldValueContainerBefore) {
            $customField = $this->getCustomField($customFieldValueContainerBefore->getCustomFieldId());
            if (!$comparableAfter->get($customFieldValueContainerBefore->getCustomFieldId())) {
                $attributeName = $this->provideCustomFieldName($customFieldValueContainerBefore);
                $change->addAttributeChange(
                    $attributeName,
                    $this->getStorableRepresentation($customField, $customFieldValueContainerBefore),
                    ''
                );
            }
        }
    }


    /**
     * Provide custom field name
     *
     * @param CustomFieldValueContainer $customFieldValueContainer Custom field value container
     * @return string Name
     */
    private function provideCustomFieldName(
        CustomFieldValueContainer $customFieldValueContainer
    ): string {
        $attributeName = 'Feld #' . $customFieldValueContainer->getCustomFieldId();
        $customField   = $this->getCustomField($customFieldValueContainer->getCustomFieldId());
        if ($customField) {
            $attributeName = $customField->getManagementTitle() . ' [' . $attributeName . ']';
        }

        return $attributeName;
    }


    /**
     * Get a textual/scalar representation which can be stored in log
     *
     * @param Attribute|null            $customField
     * @param CustomFieldValueContainer $customFieldValueContainer
     * @return string|int|float|null
     */
    private function getStorableRepresentation(
        ?Attribute                $customField,
        CustomFieldValueContainer $customFieldValueContainer
    ) {
        $customFieldValue = $customFieldValueContainer->getValue();

        if ($customFieldValue instanceof TextualCustomFieldValue) {
            $result = $customFieldValue->getValue();
        } elseif ($customFieldValue instanceof NumberCustomFieldValue) {
            $result = $customFieldValue->getValue();
        } elseif ($customFieldValue instanceof DateCustomFieldValue) {
            if ($customFieldValue->getValue() instanceof \DateTimeInterface) {
                $result = $customFieldValue->getValue()->format('d.m.Y');
            } else {
                $result = null;
            }
        } elseif ($customFieldValue instanceof BankAccountCustomFieldValue) {
            if (!$customFieldValue->getIban()
            ) {
                $result = null;
            } else {
                $result = 'IBAN: ' . $customFieldValue->getIban() . ', BIC: ' . $customFieldValue->getBic();
                if ($customFieldValue->getOwner()) {
                    $result .= ' (' . $customFieldValue->getOwner() . ')';
                }
            }
        } elseif ($customFieldValue instanceof OptionProvidingCustomFieldValueInterface) {
            // {@see ChoiceCustomFieldValue}, {@see GroupCustomFieldValue}
            $selectedChoiceIds = $customFieldValue->getSelectedChoices();
            $selectedChoices   = [];

            if (count($selectedChoiceIds)) {
                foreach ($selectedChoiceIds as $selectedChoiceId) {
                    $selectedChoice = $customField ? $customField->getChoiceOption($selectedChoiceId) : null;
                    if ($selectedChoice) {
                        $selectedChoices[] = $selectedChoice->getManagementTitle(true) . ' [#' . $selectedChoiceId .
                                             ']';
                    } else {
                        $selectedChoices[] = '#' . $selectedChoiceId;
                    }
                }

                $result = implode(', ', $selectedChoices);
            } else {
                $result = null;
            }
        } elseif ($customFieldValue instanceof ParticipantDetectingCustomFieldValue) {
            if ($customFieldValue->getParticipantAid()) {
                $result = $customFieldValue->getParticipantFirstName()
                          . ' '
                          . $customFieldValue->getParticipantLastName();
                if ($customFieldValue->isSystemSelection()) {
                    $result .= ' [Automatisch verknüpft]';
                } else {
                    $result .= ' [verknüpft]';
                }
            } else {
                $result = $customFieldValue->getRelatedFirstName() . ' '
                          . $customFieldValue->getRelatedLastName()
                          . ' [nicht verknüpft]';
            }
        } else {
            throw new \InvalidArgumentException('Unknown class ' . get_class($customFieldValue) . ' occurred');
        }

        if ($customFieldValueContainer->hasComment()) {
            $result = (string)$result;
            $result .= "\nAnmerkung: " . $customFieldValueContainer->getComment();
        }
        return $result;
    }
}
