<?php

namespace AppBundle\Form;

use libphonenumber\PhoneNumberFormat;
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
            'tel',
            array(
                'default_region' => 'DE',
                'format'         => PhoneNumberFormat::NATIONAL,
                'label'          => 'Telefonnummer',
                'attr'           => array('aria-describedby' => 'help-info-phone-number')
            )
        )
                ->add(
                    'description',
                    TextType::class,
                    array(
                        'label'    => 'Hinweis',
                        'required' => false,
                        'attr'     => array('aria-describedby' => 'help-info-phone-description')
                    )
                );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'AppBundle\Entity\PhoneNumber',
            )
        );
    }
}
