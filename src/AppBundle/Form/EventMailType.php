<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EventMailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', TextType::class, array('label' => 'Betreff'))
            ->add('title', TextType::class, array('label' => 'Titel'))
            ->add('lead', TextType::class, array('label' => 'Untertitel', 'required' => false))
            ->add('content', TextareaType::class, array('label' => 'Hauptinhalt'));
    }

}
