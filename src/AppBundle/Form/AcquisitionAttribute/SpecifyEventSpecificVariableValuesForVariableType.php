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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpecifyEventSpecificVariableValuesForVariableType extends AbstractType
{
    const FIELD_VARIABLE = 'variable';
    const FIELD_EVENTS   = 'events';
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EventSpecificVariable $variable */
        $variable = $options[self::FIELD_VARIABLE];
        $events   = $options[self::FIELD_EVENTS];
        
        /** @var Event $event */
        foreach ($events as $event) {
            
            $data = null;
            $eid  = $event->getEid();
            $vid  = $variable->getId();
            
            $values = $variable->getValues();
            /** @var EventSpecificVariableValue $value */
            foreach ($values as $value) {
                if ($value->getEvent()->getEid() === $eid
                    && $value->getVariable()->getId() === $vid
                ) {
                    $data = $value;
                    break;
                }
            }
            
            $builder
                ->add(
                    'event_' . $event->getEid(),
                    EventSpecificVariableValueType::class,
                    [
                        'data'                                         => $data,
                        'label'                                        => $event->getTitle(),
                        'required'                                     => !$variable->hasDefaultValue(),
                        'mapped'                                       => false,
                        EventSpecificVariableValueType::FIELD_VARIABLE => $variable,
                        EventSpecificVariableValueType::FIELD_EVENT    => $event,
                    ]
                );
        }
    }
    
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::FIELD_VARIABLE);
        $resolver->setAllowedTypes(self::FIELD_VARIABLE, EventSpecificVariable::class);
        
        $resolver->setRequired(self::FIELD_EVENTS);
        $resolver->setAllowedTypes(self::FIELD_EVENTS, 'array');
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
                self::FIELD_EVENTS   => $options[self::FIELD_EVENTS]
            ]
        );
    }
}