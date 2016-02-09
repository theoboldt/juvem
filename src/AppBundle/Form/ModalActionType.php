<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ModalActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('area', HiddenType::class)
            ->add('action', HiddenType::class)
            ->add('id', HiddenType::class)
            ->add(
                'cancel', ButtonType::class, array('attr'  => array('class'        => 'btn-default',
                                                                    'data-dismiss' => 'modal'
            ),
                                                   'label' => 'Abbrechen'
            ))
            ->add(
                'submit', SubmitType::class, array('attr' => array('class' => 'btn-primary',
                                                                   'label' => 'Speichern'
            )
            ));
    }
}
