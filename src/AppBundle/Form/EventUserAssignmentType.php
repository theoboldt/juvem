<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventUserAssignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventUserAssignmentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $smartCheckbox = [
            'required' => false,
            'mapped'   => true,
        ];

        $builder
            ->add(
                'allowedToEdit',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Darf Veranstaltungsdaten bearbeiten'])
            )
            ->add(
                'allowedToManageParticipants',
                CheckboxType::class,
                array_merge(
                    $smartCheckbox,
                    ['label' => 'Darf Teilnehmer verwalten, Teilnehmerdaten bearbeiten, bestätigen und ablehnen']
                )
            )
            ->add(
                'allowedToReadComments',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Darf Bemerkungen zu Teilnehmern lesen'])
            )
            ->add(
                'allowedToComment',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Darf Bemerkungen zu Teilnehmern hinzufügen'])
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('event');
        $resolver->setAllowedTypes('event', Event::class);

        $resolver->setDefaults(
            [
                'data_class' => EventUserAssignment::class,
                'empty_data' => function (FormInterface $form) {
                    $event      = $form->getConfig()->getOption('event');
                    $assignment = new EventUserAssignment($event);
                    return $assignment;
                },
            ]
        );
    }
}
