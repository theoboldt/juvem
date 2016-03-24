<?php

namespace AppBundle\Form;

use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
                'label' => 'Titel (Intern)',
                'required' => false
            )
        )
                ->add(
                    'managementDescription',
                    TextType::class,
                    array(
                        'label' => 'Beschreibung (Intern)',
                        'required' => false
                    )
                )
                ->add(
                    'formTitle',
                    TextType::class,
                    array(
                        'label' => 'Titel (im Formular)',
                        'required' => false
                    )
                )
                ->add(
                    'formDescription',
                    TextType::class,
                    array(
                        'label' => 'Beschreibung (im Formular)',
                        'required' => false
                    )
                )
                ->add(
                    'useAtParticipation',
                    CheckboxType::class,
                    array(
                        'label' => 'Je Anmeldung erfassen',
                        'required' => false
                    )
                )
                ->add(
                    'useAtParticipant',
                    CheckboxType::class,
                    array(
                        'label' => 'Je Teilnehmer erfassen',
                        'required' => false
                    )
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

    public function getName()
    {
        return 'app_bundle_acquisition_attribute';
    }
}
