<?php

namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class ParticipationType extends ParticipationBaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'acceptPrivacy',
            CheckboxType::class,
            array(
                'label'    => 'Ich habe die Datenschutzerklärung gelesen und erkläre mich mit den Angaben einverstanden. Ich kann diese Erklärung jederzeit Wiederrufen.',
                'required' => true,
                'mapped'   => false
            )
        /*
        )
        ->add(
            'acceptLegal',
            CheckboxType::class,
            array(
                'label'    => 'Ich akzeptiere die Allgemeinen Geschäftsbedingungen',
                'required' => true,
                'mapped'   => false
            )
        */
        );
    }
}
