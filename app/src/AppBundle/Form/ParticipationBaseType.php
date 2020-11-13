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

use AppBundle\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationBaseType extends AbstractType
{
    use AcquisitionAttributeIncludingTypeTrait;

    const ACQUISITION_FIELD_PUBLIC = 'acquisitionFieldPublic';

    const ACQUISITION_FIELD_PRIVATE = 'acquisitionFieldPrivate';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Participation $participation */
        $participation = $options['data'];

        $builder
            ->add(
                'salutation',
                ChoiceType::class,
                [
                    'label'    => 'Anrede',
                    'choices'  => ['Frau' => 'Frau', 'Herr' => 'Herr'],
                    'expanded' => false,
                    'required' => true
                ]
            )
            ->add('nameFirst', TextType::class, ['label' => 'Vorname', 'required' => true])
            ->add('nameLast', TextType::class, ['label' => 'Nachname', 'required' => true])
            ->add(
                'addressStreet', TextType::class,
                [
                    'label'    => 'Straße u. Hausnummer',
                    'required' => true,
                    'attr'     => ['data-typeahead-source' => 'address_street']
                ]
            )
            ->add(
                'addressZip',
                TextType::class,
                [
                    'label'    => 'Postleitzahl',
                    'required' => true,
                    'attr'     => ['data-typeahead-source' => 'address_zip']
                ]
            )
            ->add(
                'addressCity',
                TextType::class,
                [
                    'label'    => 'Stadt',
                    'required' => true,
                    'attr'     => ['data-typeahead-source' => 'address_city']
                ]
            )
            ->add(
                'addressCountry',
                TextType::class,
                [
                    'label'    => 'Land',
                    'required' => true,
                    'attr'     => ['data-typeahead-source' => 'address_country']
                ]
            )
            ->add('email', EmailType::class, ['label' => 'E-Mail', 'required' => true])
            ->add(
                'phoneNumbers',
                CollectionType::class,
                [
                    'label'        => 'Telefonnummern',
                    'entry_type'   => PhoneNumberType::class,
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'attr'         => ['aria-describedby' => 'help-info-phone-numbers'],
                    'required'     => true
                ]
            )
            ->add(
                'participants',
                CollectionType::class,
                [
                    'label'         => 'Teilnehmer',
                    'entry_type'    => ParticipantType::class,
                    'allow_add'     => true,
                    'allow_delete'  => true,
                    'entry_options' => [
                        'participation'                 => $participation,
                        self::ACQUISITION_FIELD_PUBLIC  => $options[self::ACQUISITION_FIELD_PUBLIC],
                        self::ACQUISITION_FIELD_PRIVATE => $options[self::ACQUISITION_FIELD_PRIVATE],
                    ],
                    'required'      => true,
                ]
            );

        $event                = $participation->getEvent();
        $attributes           = $event->getAcquisitionAttributes(
            true, false, false, $options[self::ACQUISITION_FIELD_PRIVATE], $options[self::ACQUISITION_FIELD_PUBLIC]
        );

        $this->addAcquisitionAttributesToBuilder(
            $builder,
            $attributes,
            isset($options['data']) ? $options['data'] : null
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::ACQUISITION_FIELD_PUBLIC);
        $resolver->setAllowedTypes(self::ACQUISITION_FIELD_PUBLIC, 'bool');

        $resolver->setRequired(self::ACQUISITION_FIELD_PRIVATE);
        $resolver->setAllowedTypes(self::ACQUISITION_FIELD_PRIVATE, 'bool');

        $resolver->setDefaults(
            [
                'data_class'         => Participation::class,
                'cascade_validation' => true,
            ]
        );
    }
}
