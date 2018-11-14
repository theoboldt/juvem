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

use AppBundle\BitMask\ParticipantFood;
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\Transformer\AcquisitionAttributeFilloutTransformer;
use AppBundle\Form\Transformer\FoodTransformer;
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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $foodMask   = new ParticipantFood();
        $foodLabels = $foodMask->labels();
        unset($foodLabels[ParticipantFood::TYPE_FOOD_VEGAN]);

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
                    'choices'  => [
                        Participant::LABEL_GENDER_FEMALE => Participant::TYPE_GENDER_FEMALE,
                        Participant::LABEL_GENDER_MALE   => Participant::TYPE_GENDER_MALE,
                    ],
                    'required' => true,
                ]
            )
            ->add(
                'birthday',
                DateType::class,
                ['label'    => 'Geburtsdatum',
                 'years'    => range(Date('Y') - 30, Date('Y') - 3),
                 //                      'widget' => 'single_text',
                 //                      'format' => 'yyyy-MM-dd',
                 'format'   => 'dd.MM.yyyy',
                 'required' => true,
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
            )
            ->add(
                'food',
                ChoiceType::class,
                [
                    'label'    => 'Ernährung',
                    'choices'  => array_flip($foodLabels),
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                    'attr'     => [
                        'aria-describedby'         => 'help-food',
                        'class'                    => 'food-options',
                        'data-food-lactose-option' => ParticipantFood::TYPE_FOOD_LACTOSE_FREE,
                    ],
                ]
            );

        /** @var Participation $participation */
        $participation        = $options[self::PARTICIPATION_FIELD];
        $event                = $participation->getEvent();
        $attributes           = $event->getAcquisitionAttributes(
            false, true, false, $options[self::ACQUISITION_FIELD_PRIVATE], $options[self::ACQUISITION_FIELD_PUBLIC]
        );
        $attributeTransformer = new AcquisitionAttributeFilloutTransformer();

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $bid = $attribute->getBid();

            $attributeOptions = $attribute->getFieldOptions();
            if ($attribute->getFieldTypeChoiceType()) {
                $attributeOptions['empty_data'] = [];
            }

            try {
                if (isset($options['data']) && $options['data'] instanceof Participant) {
                    /** @var Fillout $fillout */
                    $fillout                  = $options['data']->getAcquisitionAttributeFillout($bid);
                    $attributeOptions['data'] = $fillout->getValue()->getFormValue();
                }
            } catch (\OutOfBoundsException $e) {
                //intentionally left empty
            }
            $builder->add(
                $attribute->getName(),
                $attribute->getFieldType(),
                array_merge($attributeOptions, $attribute->getFieldOptions())
            );
            $builder->get($attribute->getName())->addModelTransformer($attributeTransformer);
        }

        $builder->get('food')->addModelTransformer(new FoodTransformer());
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
