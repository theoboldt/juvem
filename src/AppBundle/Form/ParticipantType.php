<?php

namespace AppBundle\Form;

use AppBundle\Entity\Participant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'nameFirst',
                TextType::class,
                array('label' => 'Vorname')
            )
            ->add(
                'nameLast',
                TextType::class,
                array('label' => 'Nachname')
            )
            ->add(
                'birthday',
                DateType::class,
                array('label' => 'Geburtsdatum')
            )
            ->add(
                'infoMedical',
                TextareaType::class,
                array('label' => 'Medizinische Hinweise', 'attr' => array('aria-describedby' => 'help-info-medical'))
            )
            ->add(
                'infoGeneral',
                TextareaType::class,
                array('label' => 'Allgemeine Hinweise', 'attr' => array('aria-describedby' => 'help-info-general'))
            )
            ->add('food', ChoiceType::class, array(
                'label'             => 'Ernährung',
                'choices'           => array(
                    Participant::LABEL_FOOD_VEGAN      => Participant::TYPE_FOOD_VEGAN,
                    Participant::LABEL_FOOD_VEGETARIAN => Participant::TYPE_FOOD_VEGETARIAN,
                    Participant::LABEL_FOOD_NO_PORK    => Participant::TYPE_FOOD_NO_PORK
                ),
                'choices_as_values' => true, 'expanded' => true, 'multiple' => true,
                'attr'              => array('aria-describedby' => 'help-food')
            ));
    }

    public function getName()
    {
        return 'app_bundle_participant';
    }
}
