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

use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\CustomField\CustomFieldValuesType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    const PARTICIPATION_FIELD = 'participation';

    const ACQUISITION_FIELD_PUBLIC = 'acquisitionFieldPublic';

    const ACQUISITION_FIELD_PRIVATE = 'acquisitionFieldPrivate';

    /**
     * @var string[]
     */
    private $excludedGenderChoices = [];

    /**
     * ParticipantType constructor.
     *
     * @param string $excludedGenderChoices
     */
    public function __construct(string $excludedGenderChoices)
    {
        $excludedGenderChoices = explode(';', $excludedGenderChoices);
        if (count($excludedGenderChoices) && !empty($excludedGenderChoices[0])) {
            $this->excludedGenderChoices = $excludedGenderChoices;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Participant $participant */
        $participant = $options['data'] ?? null;
        
        $genderChoices = [
            ''                                     => '',
            Participant::LABEL_GENDER_FEMALE       => Participant::LABEL_GENDER_FEMALE,
            Participant::LABEL_GENDER_MALE         => Participant::LABEL_GENDER_MALE,
            Participant::LABEL_GENDER_FEMALE_ALIKE => Participant::LABEL_GENDER_FEMALE_ALIKE,
            Participant::LABEL_GENDER_MALE_ALIKE   => Participant::LABEL_GENDER_MALE_ALIKE,
            Participant::LABEL_GENDER_DIVERSE      => Participant::LABEL_GENDER_DIVERSE,
            Participant::LABEL_GENDER_OTHER        => Participant::LABEL_GENDER_OTHER,
        ];
        foreach ($this->excludedGenderChoices as $excludedGenderChoice) {
            if (isset($genderChoices[$excludedGenderChoice])) {
                unset($genderChoices[$excludedGenderChoice]);
            }
        }

        $builder
            ->add(
                'nameFirst',
                TextType::class,
                ['label' => 'Vorname', 'required' => true]
            )
            ->add(
                'nameLast',
                TextType::class,
                ['label' => 'Nachname', 'required' => true]
            )
            ->add(
                'gender',
                ChoiceType::class,
                [
                    'label'    => 'Geschlecht',
                    'choices'  => $genderChoices,
                    'required' => true,
                ]
            )
            ->add(
                'birthday',
                DateType::class,
                [
                    'label'    => 'Geburtsdatum',
                    'years'    => range(Date('Y') - 90, Date('Y') - 3),
                    //                      'widget' => 'single_text',
                    //                      'format' => 'yyyy-MM-dd',
                    'format'   => 'dd.MM.yyyy',
                    'required' => true,
                    'attr'     => ['class' => 'form-date'],
                ]
            )
            ->add(
                'infoMedical',
                TextareaType::class,
                ['label'      => 'Medizinische Hinweise',
                 'attr'       => ['aria-describedby' => 'help-info-medical'],
                 'required'   => false,
                 'empty_data' => '',
                 //may not work due to issue https://github.com/symfony/symfony/issues/5906
                ]
            )
            ->add(
                'infoGeneral',
                TextareaType::class,
                ['label'      => 'Allgemeine Hinweise',
                 'attr'       => ['aria-describedby' => 'help-info-general'],
                 'required'   => false,
                 'empty_data' => '',
                 //may not work due to issue https://github.com/symfony/symfony/issues/5906
                ]
            );

        /** @var Participation $participation */
        $participation = $options[self::PARTICIPATION_FIELD];
        $event         = $participation->getEvent();

        $builder->add(
            'customFieldValues',
            CustomFieldValuesType::class,
            [
                'by_reference'                                => true,
                'mapped'                                      => true,
                'cascade_validation'                          => true,
                CustomFieldValuesType::ENTITY_OPTION          => $participant,
                CustomFieldValuesType::RELATED_CLASS_OPTION   => Participant::class,
                CustomFieldValuesType::RELATED_EVENT          => $event,
                CustomFieldValuesType::INCLUDE_PRIVATE_OPTION => $options[self::ACQUISITION_FIELD_PRIVATE],
                CustomFieldValuesType::INCLUDE_PUBLIC_OPTION  => $options[self::ACQUISITION_FIELD_PUBLIC],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::PARTICIPATION_FIELD);
        $resolver->setAllowedTypes(self::PARTICIPATION_FIELD, Participation::class);

        $resolver->setRequired(self::ACQUISITION_FIELD_PUBLIC);
        $resolver->setAllowedTypes(self::ACQUISITION_FIELD_PUBLIC, 'bool');

        $resolver->setRequired(self::ACQUISITION_FIELD_PRIVATE);
        $resolver->setAllowedTypes(self::ACQUISITION_FIELD_PRIVATE, 'bool');

        $resolver->setDefaults(
            [
                'data_class' => Participant::class,
                'empty_data' => function (FormInterface $form) {
                    $participation = $form->getConfig()->getOption('participation');
                    $participant   = new Participant($participation);
                    return $participant;
                },
            ]
        );
    }
}
