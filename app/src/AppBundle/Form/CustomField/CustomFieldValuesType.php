<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\CustomField;

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\CustomField\CustomFieldValueCollection;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomFieldValuesType extends AbstractType
{

    const RELATED_EVENT          = 'event';
    const RELATED_CLASS_OPTION   = 'related_class';
    const ENTITY_OPTION          = 'entity';
    const INCLUDE_PUBLIC_OPTION  = 'include_public';
    const INCLUDE_PRIVATE_OPTION = 'include_private';

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ?EntityHavingCustomFieldValueInterface $entity */
        $entity = $options[self::ENTITY_OPTION];
        /** @var ?Event $entity */
        $event = $options[self::RELATED_EVENT];
        /** @var ?string $entity */
        $relatedClass = $options[self::RELATED_CLASS_OPTION];

        $includePrivate = $options[self::INCLUDE_PRIVATE_OPTION];
        $includePublic  = $options[self::INCLUDE_PUBLIC_OPTION];

        $customFields = self::provideCustomFields($entity, $event, $relatedClass, $includePrivate, $includePublic);

        foreach ($customFields as $customField) {
            $builder->add(
                $customField->getCustomFieldName(),
                CustomFieldValueContainerType::class,
                [
                    'by_reference'                                     => true,
                    'label'                                            => $customField->getFormTitle(),
                    'required'                                         => $customField->isRequired(),
                    'mapped'                                           => true,
                    CustomFieldValueContainerType::CUSTOM_FIELD_OPTION => $customField,
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('label', 'Zusatzfelder');
        
        $resolver->setRequired(self::INCLUDE_PRIVATE_OPTION);
        $resolver->setAllowedTypes(self::INCLUDE_PRIVATE_OPTION, 'bool');

        $resolver->setRequired(self::INCLUDE_PUBLIC_OPTION);
        $resolver->setAllowedTypes(self::INCLUDE_PUBLIC_OPTION, 'bool');
        
        $resolver->setDefault(self::ENTITY_OPTION, null);
        $resolver->setAllowedTypes(self::ENTITY_OPTION, [EntityHavingCustomFieldValueInterface::class, 'null']);

        $resolver->setDefault(self::RELATED_EVENT, null);
        $resolver->setAllowedTypes(self::RELATED_EVENT, [Event::class, 'null']);

        $resolver->setDefault(self::RELATED_CLASS_OPTION, null);
        $resolver->setAllowedTypes(self::RELATED_CLASS_OPTION, ['string', 'null']);
        $resolver->setAllowedValues(
            self::RELATED_CLASS_OPTION, [null, Participation::class, Participant::class, Employee::class]
        );

        $resolver->setDefault('cascade_validation', true);

        $resolver->setDefault('data_class', CustomFieldValueCollection::class);
        $resolver->setDefault('empty_data', function (FormInterface $form) {
            $config = $form->getConfig();
            $entity = $config->getOption(self::ENTITY_OPTION);
            if ($entity) {
                if (!$entity instanceof EntityHavingCustomFieldValueInterface) {
                    throw new \InvalidArgumentException(
                        'Expecting instance of ' . EntityHavingCustomFieldValueInterface::class
                    );
                }
                return $entity->getCustomFieldValues();
            } else {
                return new CustomFieldValueCollection();
            }
        });
    }


    /**
     * Extract custom fields from entity
     *
     * @param EntityHavingCustomFieldValueInterface|null $entity
     * @param Event|null                                 $event
     * @param string|null                                $relatedClass
     * @param bool                                       $includePrivate
     * @param bool                                       $includePublic
     * @return Attribute[]
     */
    private static function provideCustomFields(
        ?EntityHavingCustomFieldValueInterface $entity,
        ?Event                                 $event,
        ?string                                $relatedClass,
        bool                                   $includePrivate,
        bool                                   $includePublic
    ): array {
        if ($entity && $event !== null && $entity->getEvent() !== $event) {
            throw new \InvalidArgumentException('Event configured at entity differs from directly configured one');
        }
        if (!$event) {
            if (!$entity) {
                throw new \InvalidArgumentException('Either entity or directly event must be configured');
            }
            $event = $entity->getEvent();
        }

        if ($entity instanceof Participation || $relatedClass === Participation::class) {
            $customFields = $event->getAcquisitionAttributes(true, false, false, $includePrivate, $includePublic);
        } elseif ($entity instanceof Participant || $relatedClass === Participant::class) {
            $customFields = $event->getAcquisitionAttributes(false, true, false, $includePrivate, $includePublic);
        } elseif ($entity instanceof Employee || $relatedClass === Employee::class) {
            $customFields = $event->getAcquisitionAttributes(false, false, true, $includePrivate, $includePublic);
        } else {
            throw new \InvalidArgumentException('Unknown entity provided');
        }

        return $customFields;
    }

}
