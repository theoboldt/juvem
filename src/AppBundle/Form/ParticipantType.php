<?php

namespace AppBundle\Form;

use AppBundle\BitMask\ParticipantFood;
use AppBundle\Entity\AcquisitionAttribute;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $foodMask = new ParticipantFood();

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
                        Participant::LABEL_GENDER_MALE   => Participant::TYPE_GENDER_MALE,
                        Participant::LABEL_GENDER_FEMALE => Participant::TYPE_GENDER_FEMALE
                    ],
                    'required' => true
                ]
            )
            ->add(
                'birthday',
                DateType::class,
                ['label'  => 'Geburtsdatum',
                 'years'  => range(Date('Y') - 30, Date('Y') - 3),
                 //                      'widget' => 'single_text',
                 //                      'format' => 'yyyy-MM-dd',
                 'format' => 'dd.MM.yyyy',
                 'required' => true
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
                    'choices'  => array_flip($foodMask->labels()),
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-food']
                ]
            );

        /** @var Participation $participation */
        $participation = $options['participation'];
        $event         = $participation->getEvent();
        $attributes    = $event->getAcquisitionAttributes(false, true);

        /** @var AcquisitionAttribute $attribute */
        foreach ($attributes as $attribute) {
            $bid              = $attribute->getBid();
            $attributeOptions = [
                'label'    => $attribute->getFormTitle(),
                'required' => $attribute->isRequired()
            ];
            if (ChoiceType::class == $attribute->getFieldType()) {
                $attributeOptions['placeholder'] = 'keine Option gewählt';
            }
            if (isset($attributeOptions['multiple']) && $attributeOptions['multiple']) {
                $attributeOptions['data'] = [];
            }

            try {
                if (isset($options['data']) && $options['data'] instanceof Participant) {
                    $fillout                  = $options['data']->getAcquisitionAttributeFillout($bid);
                    $attributeOptions['data'] = $fillout->getValue();
                }
            } catch (\OutOfBoundsException $e) {
                //intentionally left empty
            }
            $builder->add(
                $attribute->getName(),
                $attribute->getFieldType(),
                array_merge($attributeOptions, $attribute->getFieldOptions())
            );
        }

        $builder->get('food')
                ->addModelTransformer(
                    new CallbackTransformer(
                        function ($originalFoodSum) {
                            $mask = new ParticipantFood($originalFoodSum);
                            return $mask->getActiveList();
                        },
                        function ($submittedFood) {
                            return new ParticipantFood(array_sum($submittedFood));
                        }
                    )
                );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('participation');
        $resolver->setAllowedTypes('participation', Participation::class);

        $resolver->setDefaults(
            [
                'data_class' => Participant::class,
            ]
        );
    }
}
