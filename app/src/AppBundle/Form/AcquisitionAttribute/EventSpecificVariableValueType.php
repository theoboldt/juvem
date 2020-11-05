<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\AcquisitionAttribute;

use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariableValue;
use AppBundle\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class EventSpecificVariableValueType extends AbstractType
{
    const FIELD_EVENT    = 'event';
    const FIELD_VARIABLE = 'variable';

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EventSpecificVariable $variable */
        $variable = $options[self::FIELD_VARIABLE];

        $optionsNumber = [
            'label'    => 'Wert',
            'required' => false,
            'mapped'   => true,
            'attr'     => [
                'aria-describedby' => 'help-value',
                'placeholder'      => $variable->hasDefaultValue() ? $variable->getDefaultValue() : ''
            ],
        ];

        if (!$variable->hasDefaultValue()) {
            $optionsNumber['required']    = true;
            $optionsNumber['constraints'] = [
                new NotBlank(),
                new Range(['min' => PHP_INT_MIN, 'max' => PHP_INT_MAX,]),
            ];
        }

        $builder
            ->add(
                'value',
                NumberType::class,
                $optionsNumber
            );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::FIELD_VARIABLE);
        $resolver->setAllowedTypes(self::FIELD_VARIABLE, EventSpecificVariable::class);
        $resolver->setRequired(self::FIELD_EVENT);
        $resolver->setAllowedTypes(self::FIELD_EVENT, Event::class);
        $resolver->setDefaults(
            [
                'empty_data' => function (FormInterface $form) {
                    $event    = $form->getConfig()->getOption(self::FIELD_EVENT);
                    $eid      = $event->getEid();
                    $variable = $form->getConfig()->getOption(self::FIELD_VARIABLE);
                    $vid      = $variable->getId();

                    $values = $variable->getValues();
                    /** @var EventSpecificVariableValue $value */
                    foreach ($values as $value) {
                        if ($value->getEvent()->getEid() === $eid
                            && $value->getVariable()->getId() === $vid
                        ) {
                            return $value;
                        }
                    }

                    return new EventSpecificVariableValue($event, $variable, null);
                },
                'data_class' => EventSpecificVariableValue::class,
            ]
        );
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
                self::FIELD_VARIABLE => $options[self::FIELD_VARIABLE],
                self::FIELD_EVENT    => $options[self::FIELD_EVENT]
            ]
        );
    }
}
