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
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomFieldValueContainerType extends AbstractType
{

    const CUSTOM_FIELD_OPTION = 'custom_field';

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Attribute $customField */
        $customField = $options[self::CUSTOM_FIELD_OPTION];

        $attributeOptions = [
            'by_reference' => true,
        ];
        if ($customField->isRequired()) {
            switch ($customField->getFieldType()) {
                case ChoiceType::class:
                    if ($customField->isMultipleChoiceType()) {
                        $message = 'Mindestens eine Option muss ausgewählt sein.';
                    } else {
                        $message = 'Eine Option muss ausgewählt sein.';
                    }
                    $attributeOptions['constraints'] = [
                        new NotBlank(['message' => $message]),
                    ];
                    break;
                default:
                    $attributeOptions['constraints'] = [
                        new NotBlank(),
                    ];
                    break;
            }
        }

        $attributeOptions['row_attr']      = ['class' => 'custom-field-field-row'];
        $attributeOptions['attr']          = [
            'class'                 => 'custom-field-field custom-field-field-'.$customField->getBid(),
            'data-typeahead-source' => $customField->getCustomFieldName(),
        ];
        $attributeOptions['by_reference']  = true;
        $attributeOptions['property_path'] = 'value.formValue';
        $builder->add(
            'value',
            $customField->getFieldType(),
            array_merge($attributeOptions, $customField->getFieldOptions())
        );
        if ($customField->isChoiceType() && !$customField->isMultipleChoiceType()) {
            $builder->get('value')->addModelTransformer(
                new CallbackTransformer(
                    function ($selectedChoices) {
                        if (is_array($selectedChoices)) {
                            if (count($selectedChoices) === 1) {
                                return reset($selectedChoices);
                            } elseif (count($selectedChoices) > 1) {
                                return reset($selectedChoices); // TODO check for implementation errors
                            }
                        }
                        return null;
                    },
                    function ($selectedChoice) {
                        if (is_numeric($selectedChoice) || is_string($selectedChoice)) {
                            return [$selectedChoice];
                        } else {
                            return [];
                        }
                    }
                )
            );
        }

        if ($customField->isCommentEnabled()) {
            $builder->add(
                'comment',
                TextType::class,
                [
                    'label'        => 'Ergänzungen zu ' . $customField->getFormTitle(),
                    'required'     => false,
                    'by_reference' => true,
                    'row_attr'     => [
                        'class' => 'comment-field-row',
                    ],
                    'attr'         => [
                        'class' => 'comment-field',
                    ],
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
        $resolver->setRequired(self::CUSTOM_FIELD_OPTION);
        $resolver->setAllowedTypes(self::CUSTOM_FIELD_OPTION, [Attribute::class]);

        $resolver->setDefault('cascade_validation', true);

        $resolver->setDefault('data_class', CustomFieldValueContainer::class);
        $resolver->setDefault('empty_data', function (FormInterface $form) {
            /** @var Attribute $customField */
            $customField = $form->getConfig()->getOption(self::CUSTOM_FIELD_OPTION);

            $container = new CustomFieldValueContainer(
                $customField->getBid(),
                $customField->getCustomFieldValueType(),
                null
            );
            return $container;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars = array_merge(
            $view->vars,
            [
                self::CUSTOM_FIELD_OPTION => $options[self::CUSTOM_FIELD_OPTION],
            ]
        );
    }
}
