<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\SearchParticipant;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchParticipantType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'eventFilter',
                ChoiceType::class,
                [
                    'label'    => 'Veranstaltungen',
                    'choices'  => [
                        'Nur aktive Veranstaltungen berücksichtigen' => ParticipantSearch::INCLUDE_EVENT_ACTIVE,
                        'Alle Veranstaltungen berücksichtigen'       => ParticipantSearch::INCLUDE_EVENT_ALL,
                    ],
                    'required' => true,
                ]
            )
            ->add(
                'participationEmail',
                TextType::class,
                ['label' => 'E-Mail', 'required' => false]
            )
            ->add(
                'participationFirstName',
                TextType::class,
                ['label' => 'Vorname (Anmeldung)', 'required' => false]
            )
            ->add(
                'participationLastName',
                TextType::class,
                ['label' => 'Nachname (Anmeldung)', 'required' => false]
            )
            ->add(
                'participantFirstName',
                TextType::class,
                ['label' => 'Vorname (Teilnehmer:in)', 'required' => false]
            )
            ->add(
                'participantLastName',
                TextType::class,
                ['label' => 'Nachname (Teilnehmer:in)', 'required' => false]
            );
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ParticipantSearch::class,
            ]
        );
    }
}
