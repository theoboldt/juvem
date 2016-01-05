<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('label' => 'Titel'))
            ->add('description', TextareaType::class, array('label' => 'Beschreibung'))
            ->add('startDate', DateType::class, array(
                'years' => range(Date('Y')-1, Date('Y')+1),
                'format' => 'dd.M.yyyy',
                'label' => 'Startdatum'
            ))
            ->add('hasStartTime', CheckboxType::class,
                array(
                    'required' => false,
                    'attr' => array('class' => 'checkbox-smart'),
                    'label' => 'Startzeit',
                    'label_attr' => array('class' => 'control-label')
                )
            )
            ->add('startTime', TimeType::class, array('label' => 'Startzeit'))
            ->add('hasEndDate', CheckboxType::class,
                array(
                    'required' => false,
                    'attr' => array('class' => 'checkbox-smart'),
                    'label' => 'Enddatum',
                    'label_attr' => array('class' => 'control-label')
                )
            )
            ->add('endDate', DateType::class, array('label' => 'Enddatum'))
            ->add('hasEndTime', CheckboxType::class,
                array(
                    'required' => false,
                    'attr' => array('class' => 'checkbox-smart'),
                    'label' => 'Endzeit',
                    'label_attr' => array('class' => 'control-label')
                )
            )
            ->add('endTime', TimeType::class, array('label' => 'Endzeit'))
            ->add('isActive', ChoiceType::class, array(
                'label' => 'Status',
                'choices' => array('Für Anmeldungen offen' => true, 'Keine Anmeldungen möglich' => false),
                'choices_as_values' => true, 'expanded' => true
            ))
            ->add('isVisible', ChoiceType::class, array(
                'label' => 'Sichtbarkeit', 'choices' => array('Aktiv' => true, 'Versteckt' => false),
                'choices_as_values' => true, 'expanded' => true
            ))
            ->add('save', SubmitType::class)
            ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            if (!$form->get('hasStartTime')->getData()) {
                $form->get('startTime')->setData(null);
            }
            if (!$form->get('hasEndDate')->getData()) {
                $form->get('endDate')->setData(null);
            }
            if (!$form->get('hasEndTime')->getData()) {
                $form->get('endTime')->setData(null);
            }
        });

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Event',
        ));
    }
}
