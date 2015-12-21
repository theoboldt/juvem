<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('save', SubmitType::class)
            ->add('title', TextType::class, array('label' => 'Titel'))
            ->add('description', TextareaType::class, array('label' => 'Beschreibung'))
            ->add('startDate', DateType::class, array('label' => 'Startdatum'))
            ->add('hasStartTime', ChoiceType::class, array(
                'label' => 'Startdatum eintragen',
                'choices' => array('Startdatum' => true, 'no' => false),
                'choices_as_values' => true, 'expanded' => true, 'multiple' => false
            ), array('mapped' => false))
            ->add('startTime', TimeType::class, array('label' => 'Startzeit'))
            ->add('endDate', DateType::class, array('label' => 'Enddatum'))
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
            ->add('save', SubmitType::class, array('label' => 'Veranstaltung erstellen'))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Event',
        ));
    }
}
