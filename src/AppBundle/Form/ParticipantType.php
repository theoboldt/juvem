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
                'gender',
                ChoiceType::class,
                array(
                    'label'             => 'Geschlecht',
                    'choices'           => array(
                        Participant::LABEL_GENDER_MALE   => Participant::TYPE_GENDER_MALE,
                        Participant::LABEL_GENDER_FEMALE => Participant::TYPE_GENDER_FEMALE
                    ),
                    'choices_as_values' => true,
                    'required'          => false,
                    'attr'              => array('aria-describedby' => 'help-food')

                )
            )
            ->add(
                'birthday',
                DateType::class,
                array('label'  => 'Geburtsdatum',
                      'years'  => range(Date('Y') - 22, Date('Y') - 3),
                      'widget' => 'single_text',
                      'format' => 'yyyy-MM-dd',
                )
            )
            ->add(
                'infoMedical',
                TextareaType::class,
                array('label' => 'Medizinische Hinweise',
                      'attr'  => array('aria-describedby' => 'help-info-medical',
                                       'required'         => false
                      )
                )
            )
            ->add(
                'infoGeneral',
                TextareaType::class,
                array('label'    => 'Allgemeine Hinweise',
                      'attr'     => array('aria-describedby' => 'help-info-general'),
                      'required' => false
                )
            )
            ->add(
                'food', ChoiceType::class, array(
                'label'             => 'Ernährung',
                'choices'           => array(
                    Participant::LABEL_FOOD_LACTOSE_FREE => Participant::TYPE_FOOD_LACTOSE_FREE,
                    Participant::LABEL_FOOD_VEGAN        => Participant::TYPE_FOOD_VEGAN,
                    Participant::LABEL_FOOD_VEGETARIAN   => Participant::TYPE_FOOD_VEGETARIAN,
                    Participant::LABEL_FOOD_NO_PORK      => Participant::TYPE_FOOD_NO_PORK
                ),
                'choices_as_values' => true,
                'expanded'          => true,
                'multiple'          => true,
                'required'          => false,
                'attr'              => array('aria-describedby' => 'help-food')
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'AppBundle\Entity\Participant',
            ));
    }

    public function getName()
    {
        return 'app_bundle_participant';
    }
}
