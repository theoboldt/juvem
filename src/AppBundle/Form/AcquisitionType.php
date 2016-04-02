<?php

namespace AppBundle\Form;

use AppBundle\Entity\AcquisitionAttribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcquisitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(
            'managementTitle',
            TextType::class,
            array(
                'label'    => 'Titel (Intern)',
                'required' => false
            )
        )
                ->add(
                    'managementDescription',
                    TextType::class,
                    array(
                        'label'    => 'Beschreibung (Intern)',
                        'required' => false
                    )
                )
                ->add(
                    'formTitle',
                    TextType::class,
                    array(
                        'label'    => 'Titel (im Formular)',
                        'required' => false
                    )
                )
                ->add(
                    'formDescription',
                    TextType::class,
                    array(
                        'label'    => 'Beschreibung (im Formular)',
                        'required' => false
                    )
                )
                ->add(
                    'fieldType',
                    ChoiceType::class,
                    array(
                        'label'             => 'Typ',
                        'choices'           => array(
                            AcquisitionAttribute::LABEL_FIELD_TEXT     => TextType::class,
                            AcquisitionAttribute::LABEL_FIELD_TEXTAREA => TextareaType::class,
                            AcquisitionAttribute::LABEL_FIELD_CHOICE   => ChoiceType::class,
                        ),
                        'choices_as_values' => true,
                        'required'          => true

                    )
                )
                ->add(
                    'useAtParticipation',
                    CheckboxType::class,
                    array(
                        'label'    => 'Je Anmeldung erfassen',
                        'required' => false
                    )
                )
                ->add(
                    'useAtParticipant',
                    CheckboxType::class,
                    array(
                        'label'    => 'Je Teilnehmer erfassen',
                        'required' => false
                    )
                )
                ->add(
                    'fieldTypeChoiceType',
                    ChoiceType::class,
                    array(
                        'label'             => 'Typ der Auswahl',
                        'label_attr'        => array('class' => 'col-sm-4 control-label required'),
                        //label_attr has to be defined here due to an error
                        'choices'           => array(
                            'Mehrere Optionen auswählbar' => 1,
                            'Nur eine Option  auswählbar' => 0
                        ),
                        'choices_as_values' => true,
                        'mapped'            => false,
                        'required'          => false
                    )
                )
                ->add(
                    'fieldTypeChoiceOptions',
                    TextType::class,
                    array(
                        'label'      => 'Optionen der Auswahl',
                        'label_attr' => array('class' => 'col-sm-4 control-label required'),
                        'attr'       => array('aria-describedby' => 'help-options'),
                        'mapped'     => false,
                        'required'   => false

                    )
                )
                ->add(
                    'fieldOptions',
                    HiddenType::class
                );


    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'AppBundle\Entity\AcquisitionAttribute',
            )
        );
    }
}
