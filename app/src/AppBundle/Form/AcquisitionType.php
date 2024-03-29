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

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcquisitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data     = isset($options['data']) ? $options['data'] : null;
        $isSystem = $data instanceof Attribute && $data->isSystem();
        
        if (!$isSystem) {
            $builder->add(
                'managementTitle',
                TextType::class,
                [
                    'label'    => 'Titel (Intern)',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-management-title'],
                ]
            )->add(
                'managementDescription',
                TextType::class,
                [
                    'label'    => 'Beschreibung (Intern)',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-management-description'],
                ]
            );
        }
        
        $builder->add(
            'formTitle',
            TextType::class,
            [
                'label'    => 'Titel (im Formular)',
                'required' => false,
                'attr'     => ['aria-describedby' => 'help-form-title'],
            ]
        )->add(
            'formDescription',
            TextareaType::class,
            [
                'label'    => 'Beschreibung (im Formular)',
                'required' => false,
                'attr'     => ['aria-describedby' => 'help-form-description'],
            ]
        );
            
        if (!$isSystem) {
            $builder->add(
                'fieldType',
                ChoiceType::class,
                [
                    'label'    => 'Typ',
                    'choices'  => [
                        Attribute::LABEL_FIELD_TEXT        => TextType::class,
                        Attribute::LABEL_FIELD_TEXTAREA    => TextareaType::class,
                        Attribute::LABEL_FIELD_NUMBER      => NumberType::class,
                        Attribute::LABEL_FIELD_CHOICE      => ChoiceType::class,
                        Attribute::LABEL_FIELD_DATE        => DateType::class,
                        Attribute::LABEL_FIELD_BANK        => BankAccountType::class,
                        Attribute::LABEL_FIELD_GROUP       => GroupType::class,
                        Attribute::LABEL_FIELD_PARTICIPANT => ParticipantDetectingType::class,
                    ],
                    'required' => true,
                    'attr'     => ['aria-describedby' => 'help-type'],
                ]
            );
        }
        $builder->add(
            'sort',
            NumberType::class,
            [
                'label'      => 'Position',
                'required'   => $data !== null && $data->getId() !== null,
                'empty_data' => ($data === null || $data->getId() === null) ? null : ($data->getId()*10),
            ]
        )->add(
            'useAtParticipation',
            CheckboxType::class,
            [
                'label'    => 'Je Anmeldung erfassen',
                'required' => false,
            ]
        )->add(
            'useAtParticipant',
            CheckboxType::class,
            [
                'label'    => 'Je Teilnehmer:in erfassen',
                'required' => false,
            ]
        )->add(
            'useAtEmployee',
            CheckboxType::class,
            [
                'label'    => 'Bei Mitarbeiter:innen erfassen',
                'required' => false,
            ]
        );
            
        if (!$isSystem) {
            $builder->add(
                'isRequired',
                CheckboxType::class,
                [
                    'label'    => 'Pflichtfeld',
                    'required' => false,
                ]
            )->add(
                'isCommentEnabled',
                CheckboxType::class,
                [
                    'label'    => 'Ergänzungen ermöglichen',
                    'required' => false,
                ]
            )->add(
                'isPublic',
                CheckboxType::class,
                [
                    'label'    => 'Sichtbarkeit',
                    'required' => false,
                ]
            )->add(
                'isMultipleChoiceType',
                ChoiceType::class,
                [
                    'label'      => 'Typ der Auswahl',
                    'label_attr' => ['class' => 'control-label required'],
                    //label_attr has to be defined here due to an error
                    'choices'    => [
                        'Mehrere Optionen auswählbar' => 1,
                        'Nur eine Option auswählbar'  => 0,
                    ],
                    'mapped'     => true,
                    'required'   => false,
                ]
            );
        }
        
        $builder->add(
            'choiceOptions',
            CollectionType::class,
            [
                'label'        => 'Optionen der Auswahl',
                'entry_type'   => AcquisitionChoiceOptionType::class,
                'by_reference' => false,
                'allow_add'    => true,
                'allow_delete' => true,
                'attr'         => ['aria-describedby' => 'help-choice-options'],
                'required'     => true,
            ]
        )->add(
            'isPriceFormulaEnabled',
            CheckboxType::class,
            [
                'label'    => 'Auswirkung auf Preis/Aufwandsentschädigung',
                'mapped'   => true,
                'required' => false,
                'attr'     => ['aria-describedby' => 'help-form-formula-enabled'],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Attribute::class,
            ]
        );
    }
}
