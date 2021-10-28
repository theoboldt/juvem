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
use AppBundle\Entity\Event;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateTypeOptions = [
            'years'  => range(Date('Y') - 1, Date('Y') + 1),
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
        ];
        $smartCheckbox   = [
            'required'   => false,
            'mapped'     => true,
            'attr'       => ['class' => 'checkbox-smart'],
            'label_attr' => ['class' => 'control-label checkbox-smart-label'],
        ];

        $builder
            ->add('title', TextType::class, ['label' => 'Titel'])
            ->add(
                'description',
                TextareaType::class,
                [
                    'label' => 'Beschreibung',
                    'attr'  => ['aria-describedby' => 'help-description', 'class' => 'markdown-editable'],
                ]
            )
            ->add(
                'descriptionMeta',
                TextareaType::class,
                [
                    'required' => false,
                    'label'    => 'Kurzbeschreibung',
                    'attr'     => ['aria-describedby' => 'help-description-short'],
                ]
            )
            ->add(
                'startDate',
                DateType::class,
                array_merge($dateTypeOptions, ['label' => 'Startdatum'])
            )
            ->add(
                'hasStartTime',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Startzeit'])
            )
            ->add('startTime', TimeType::class, ['label' => 'Startzeit'])
            ->add(
                'hasEndDate',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Enddatum'])
            )
            ->add(
                'endDate',
                DateType::class,
                array_merge($dateTypeOptions, ['label' => 'Enddatum'])
            )
            ->add(
                'hasEndTime',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Endzeit'])
            )
            ->add('endTime', TimeType::class, ['label' => 'Endzeit'])
            ->add(
                'isActive',
                ChoiceType::class, [
                    'label'    => 'Teilnehmer:innen Anmeldung',
                    'choices'  => [
                        'Offen für Anmeldungen neuer Teilnehmer:innen' => true,
                        'Keine Anmeldungen möglich'                    => false,
                    ],
                    'expanded' => true,
                ]
            )
            ->add(
                'isActiveRegistrationEmployee',
                ChoiceType::class, [
                    'label'    => 'Mitarbeiter:innen Anmeldung',
                    'choices'  => [
                        'Offen für Anmeldungen neuer Mitarbeiter:innen' => true,
                        'Keine Mitarbeiter:innen Anmeldungen möglich'   => false,
                    ],
                    'expanded' => true,
                ]
            )
            ->add(
                'isVisible',
                ChoiceType::class,
                [
                    'label'    => 'Sichtbarkeit',
                    'choices'  => [
                        'Auf Startseite präsentieren' => true,
                        'Nicht präsentieren'          => false,
                    ],
                    'expanded' => true,
                ]
            )
            ->add(
                'isAutoConfirm',
                ChoiceType::class, [
                    'label'    => 'Anmeldungs-Bestätigung (Teilnehmer:innen)',
                    'choices'  => [
                        'Eingegangene Anmeldungen einzeln bestätigen' => false,
                        'Alle Anmeldungen automatisch bestätigen'     => true,
                    ],
                    'expanded' => true,
                ]
            )
            ->add(
                'imageFile',
                VichImageType::class, [
                    'label'        => 'Poster',
                    'required'     => false,
                    'allow_delete' => true,
                    // not mandatory, default is true
                    'download_uri' => false,
                    // not mandatory, default is true
                ]
            )
            ->add(
                'invoiceTemplateFile',
                VichFileType::class,
                [
                    'label'        => 'Rechnungs-Vorlage',
                    'required'     => false,
                    'allow_delete' => true,
                    'download_uri' => false,
                ]
            )
            ->add(
                'ageRange',
                TextType::class,
                ['label'      => 'Altersspanne',
                 'required'   => false,
                 'empty_data' => null,
                 'attr'       => ['aria-describedby' => 'help-age-range'],
                ]
            )
            ->add(
                'price',
                MoneyType::class,
                [
                    'label'    => 'Preis',
                    'required' => false,
                    'divisor'  => 100,
                    'attr'     => ['aria-describedby' => 'help-price'],
                ]
            )
            ->add(
                'addressTitle',
                TextType::class,
                [
                    'label'    => 'Bezeichnung',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-address-title'],
                ]
            )
            ->add('addressStreet', TextType::class, ['label' => 'Straße u. Hausnummer', 'required' => false])
            ->add('addressZip', TextType::class, ['label' => 'Postleitzahl', 'required' => false])
            ->add('addressCity', TextType::class, ['label' => 'Stadt', 'required' => false])
            ->add(
                'addressCountry',
                TextType::class,
                [
                    'label'    => 'Land',
                    'required' => true,
                ]
            )
            ->add(
                'isShowAddress',
                CheckboxType::class,
                ['label' => 'Adresse öffentlich anzeigen', 'mapped' => true, 'required' => false]
            )
            ->add(
                'isShowMap',
                CheckboxType::class,
                ['label' => 'Karte anzeigen', 'mapped' => true, 'required' => false]
            )
            ->add(
                'isShowWeather',
                CheckboxType::class,
                ['label' => 'Wetterdaten anzeigen', 'mapped' => true, 'required' => false]
            )
            ->add(
                'hasConfirmationMessage',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Bestätigungs-Text'])
            )
            ->add(
                'confirmationMessage',
                TextareaType::class,
                [
                    'label' => 'Bestätigungs-Text',
                    'attr'  => ['aria-describedby' => 'help-confirmation-message'],
                ]
            )
            ->add(
                'hasWaitingListThreshold',
                CheckboxType::class,
                array_merge($smartCheckbox, ['label' => 'Warteliste-Schwelle'])
            )
            ->add(
                'waitingListThreshold',
                IntegerType::class,
                [
                    'label'    => 'Schwelle',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-waiting-list'],
                ]
            )
            ->add(
                'acquisitionAttributes',
                EntityType::class,
                [
                    'class'         => Attribute::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('a')
                                  ->orderBy('a.managementTitle', 'ASC');
                    },
                    'row_attr'      => [
                        'data-classes',
                    ],
                    'choice_attr'   => function (Attribute $attribute, $key, $value) {
                        $classes = [];
                        if ($attribute->isDeleted()) {
                            $classes[] = 'is-deleted';
                        }
                        if ($attribute->isArchived()) {
                            $classes[] = 'is-archived';
                        }

                        return ['class' => implode(' ', $classes)];
                    },
                    'choice_label'  => 'managementTitle',
                    'multiple'      => true,
                    'expanded'      => true,
                    'label'         => 'Bei Anmeldungen zu erfassende Felder',
                ]
            )
            ->add(
                'linkTitle',
                TextType::class,
                ['label' => 'Link-Titel (Spezial-Link)', 'required' => false]
            )
            ->add(
                'linkUrl',
                UrlType::class,
                ['label' => 'Link-URL (Spezial-Link)', 'required' => false]
            )
            ->add('save', SubmitType::class);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            if ($form->isSynchronized()) {

                if (!$form->get('hasStartTime')->getData()
                ) {
                    $form->get('startTime')->setData(null);
                }
                if (!$form->get('hasEndDate')->getData()
                ) {
                    $form->get('endDate')->setData(null);
                }
                if (!$form->get('hasEndTime')->getData()
                ) {
                    $form->get('endTime')->setData(null);
                }
            }
        }
        );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Event::class,
            ]
        );
    }
}
