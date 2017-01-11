<?php

namespace AppBundle\Form;

use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType as PhoneNumberTypeField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneNumberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(
            'number',
            PhoneNumberTypeField::class,
            [
                'default_region' => 'DE',
                'format'         => PhoneNumberFormat::NATIONAL,
                'label'          => 'Telefonnummer',
                'attr'           => ['aria-describedby' => 'help-info-phone-number'],
                'required'       => true
            ]
        )
                ->add(
                    'description',
                    TextType::class,
                    [
                        'label'    => 'Hinweis',
                        'required' => false,
                        'attr'     => ['aria-describedby' => 'help-info-phone-description']
                    ]
                );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'AppBundle\Entity\PhoneNumber',
            ]
        );
    }
}
