<?php
namespace AppBundle\Form;

use AppBundle\Entity\AttendanceList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttendanceListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titel'])
            ->add(
                'isPublicTransport',
                CheckboxType::class,
                [
                    'label'    => 'Verfügbarkeit von Fahrkarte für öffentliche Verkehrsmittel abfragen',
                    'required' => false
                ]
            )
            ->add(
                'isPaid',
                CheckboxType::class,
                [
                    'label'    => 'Bezahlung abfragen',
                    'required' => false
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => AttendanceList::class,
            ]
        );
    }
}
