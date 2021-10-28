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
                'allowedToRead',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Darf Veranstaltungsdetails und Daten der Teilnehmer:innen einsehen'])
            )
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
                    ['label' => 'Darf Daten der Teilnehmer:innen bearbeiten, bestätigen und ablehnen sowie E-Mails versenden']
                )
            )
            ->add(
                'allowedToReadComments',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Darf Bemerkungen zu Teilnehmer:innen lesen'])
            )
            ->add(
                'allowedToComment',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Darf Bemerkungen zu Teilnehmer:innen hinzufügen'])
            );
    
        $builder
            ->add(
                'allowedCloudAccessTeam',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Hat Zugriff auf die Dateien in der Mitarbeitenden-Cloud'])
            )
            ->add(
                'allowedCloudAccessManagement',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Hat Zugriff auf die Dateien in der Leitungs-Cloud'])
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
