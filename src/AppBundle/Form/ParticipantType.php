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
                array('label' => 'Vorname')
            )
            ->add(
                'nameLast',
                TextType::class,
                array('label' => 'Nachname')
            )
            ->add(
                'gender',
                ChoiceType::class,
                array(
                    'label'             => 'Geschlecht',
                    'choices'           => array(
                        Participant::LABEL_GENDER_MALE   => Participant::TYPE_GENDER_MALE,
                        Participant::LABEL_GENDER_FEMALE => Participant::TYPE_GENDER_FEMALE
                    ),
                    'choices_as_values' => true,
                    'required'          => false
                )
            )
            ->add(
                'birthday',
                DateType::class,
                array('label'  => 'Geburtsdatum',
                      'years'  => range(Date('Y') - 30, Date('Y') - 3),
                      //                      'widget' => 'single_text',
                      //                      'format' => 'yyyy-MM-dd',
                      'format' => 'dd.MM.yyyy',
                )
            )
            ->add(
                'infoMedical',
                TextareaType::class,
                array('label'      => 'Medizinische Hinweise',
                      'attr'       => array('aria-describedby' => 'help-info-medical'),
                      'required'   => false,
                      'empty_data' => '',
                      //may not work due to issue https://github.com/symfony/symfony/issues/5906
                )
            )
            ->add(
                'infoGeneral',
                TextareaType::class,
                array('label'      => 'Allgemeine Hinweise',
                      'attr'       => array('aria-describedby' => 'help-info-general'),
                      'required'   => false,
                      'empty_data' => '',
                      //may not work due to issue https://github.com/symfony/symfony/issues/5906
                )
            )
            ->add(
                'food',
                ChoiceType::class,
                array(
                    'label'             => 'Ernährung',
                    'choices'           => array_flip($foodMask->labels()),
                    'choices_as_values' => true,
                    'expanded'          => true,
                    'multiple'          => true,
                    'required'          => false,
                    'attr'              => array('aria-describedby' => 'help-food')
                )
            );

        /** @var Participation $participation */
        $participation = $options['participation'];
        $event         = $participation->getEvent();
        $attributes    = $event->getAcquisitionAttributes(false, true);

        /** @var AcquisitionAttribute $attribute */
        foreach ($attributes as $attribute) {
            $bid     = $attribute->getBid();
            $options = array(
                'label' => $attribute->getFormTitle()
            );
            try {
                $fillout         = $participation->getAcquisitionAttributeFillout($bid);
                $options['data'] = $fillout->getValue();
            } catch (\OutOfBoundsException $e) {
                //intentionally left empty
            }
            $builder->add(
                $attribute->getName(),
                $attribute->getFieldType(),
                array_merge($options, $attribute->getFieldOptions())
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
            array(
                'data_class' => Participant::class,
            )
        );
    }
}
