<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FlashType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateTypeOptions = [
            'years'       => range(Date('Y') - 1, Date('Y') + 1),
            'minutes'     => [0, 15, 30, 45],
            'date_format' => 'dd.MM.yyyy HH:mm',
        ];
        $smartCheckbox   = [
            'required'   => false,
            'mapped'     => true,
            'attr'       => ['class' => 'checkbox-smart'],
            'label_attr' => ['class' => 'control-label checkbox-smart-label']
        ];

        $builder
            ->add('message', TextareaType::class, ['label' => 'Nachricht', 'required' => true])
            ->add(
                'type', ChoiceType::class,
                [
                    'label'    => 'Typ',
                    'choices'  => [
                        'Erfolg (grün)'      => 'success',
                        'Information (blau)' => 'info',
                        'Warung (orange)'    => 'warning',
                        'Fatal (rot)'        => 'danger'
                    ],
                    'required' => true
                ]
            )
            ->add(
                'hasValidFrom', CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Gültig von'])
            )
            ->add(
                'validFrom', DateTimeType::class,
                array_merge($dateTypeOptions, ['label' => 'Gültig von'])
            )
            ->add(
                'hasValidUntil', CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Gültig bis'])
            )
            ->add(
                'validUntil', DateTimeType::class,
                array_merge($dateTypeOptions, ['label' => 'Gültig bis'])
            );


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            if (!$form->get('hasValidFrom')
                      ->getData()
            ) {
                $form->get('validFrom')
                     ->setData(null);
            }
            if (!$form->get('hasValidUntil')
                      ->getData()
            ) {
                $form->get('validUntil')
                     ->setData(null);
            }
        }
        );

    }

}
