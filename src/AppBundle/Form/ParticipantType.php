<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('participantNameFirst', TextType::class, array('label' => 'Vorname'))
            ->add('participantNameLast', TextType::class, array('label' => 'Nachname'))
            ->add('participantBirthday', DateType::class, array('label' => 'Geburtsdatum'))
            ->add('participantInfo', DateType::class, array('label' => 'Hinweise'))
            ->add('isVisible', ChoiceType::class, array(
                'label' => 'Ernährung',
                'choices' => array('vegan' => 'vegan', 'vegetarisch' => 'vegetarian', 'ohne Schweine' => 'no_pork'),
                'choices_as_values' => true, 'expanded' => true, 'multiple' => true
            ))
            ;
    }

    public function getName()
    {
        return 'app_bundle_participant';
    }
}
