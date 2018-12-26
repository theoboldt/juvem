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


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ParticipantDetectingType extends AbstractType
{

    const FIELD_NAME_FIRST_NAME = 'participantDetectingFirstName';
    const FIELD_NAME_LAST_NAME  = 'participantDetectingLastName';

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('class', 'participant-detecting');
        $builder
            ->add(
                self::FIELD_NAME_FIRST_NAME,
                TextType::class,
                [
                    'label'    => 'Vorname (verknpüfter Teilnehmer)',
                    'required' => false,
                    'attr'     => ['class' => 'col-sm-6 participant-detecting-firstname'],
                ]
            )
            ->add(
                self::FIELD_NAME_LAST_NAME,
                TextType::class,
                [
                    'label'    => 'Nachname (verknpüfter Teilnehmer)',
                    'required' => false,
                    'attr'     => ['class' => 'col-sm-6 participant-detecting-lastname'],
                ]

            );
    }
}
