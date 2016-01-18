<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateTypeOptions    = array(
            'years' => range(Date('Y')-1, Date('Y')+1),
            'format' => 'dd.M.yyyy'
        );
        $hasDateCheckbox    = array(
            'required' => false,
            'attr' => array('class' => 'checkbox-smart'),
            'label_attr' => array('class' => 'control-label')
        );

        $builder
            ->add('parentSalution', ChoiceType::class, array(
                'label' => 'Anrede',
                'choices' => array('Frau', 'Herr'),
                'choices_as_values' => false, 'expanded' => false
            ))
            ->add('parentNameFirst', TextType::class, array('label' => 'Vorname'))
            ->add('parentNameLast', TextType::class, array('label' => 'Nachname'))
            ->add('parentAdressStreet', TextType::class, array('label' => 'Straße u. Hausnummer'))
            ->add('parentAdressZip', TextType::class, array('label' => 'Postleitzahl'))
            ->add('parentAdressCity', TextType::class, array('label' => 'Stadt'))
            ->add('parentEmail', TextType::class, array('label' => 'E-Mail'))

            ->add('save', SubmitType::class)
            ;



    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
//            'data_class' => 'AppBundle\Entity\Event',
        ));
    }
}
