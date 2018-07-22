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

use AppBundle\Entity\AcquisitionAttribute;
use AppBundle\Entity\AcquisitionAttributeFillout;
use AppBundle\Entity\Participation;
use AppBundle\Form\Transformer\AcquisitionAttributeFilloutTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationBaseType extends AbstractType
{

    const ACQUISITION_FIELD_PUBLIC = 'acquisitionFieldPublic';

    const ACQUISITION_FIELD_PRIVATE = 'acquisitionFieldPrivate';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateTypeOptions = [
            'years'  => range(Date('Y') - 1, Date('Y') + 1),
            'format' => 'dd.M.yyyy'
        ];
        $hasDateCheckbox = [
            'required'   => false,
            'attr'       => ['class' => 'checkbox-smart'],
            'label_attr' => ['class' => 'control-label']
        ];
        /** @var Participation $participation */
        $participation = $options['data'];

        $builder
            ->add(
                'salution',
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
            ->add('addressStreet', TextType::class, ['label' => 'Straße u. Hausnummer', 'required' => true])
            ->add('addressZip', TextType::class, ['label' => 'Postleitzahl', 'required' => true])
            ->add('addressCity', TextType::class, ['label' => 'Stadt', 'required' => true])
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
            true, false, $options[self::ACQUISITION_FIELD_PUBLIC], $options[self::ACQUISITION_FIELD_PRIVATE]
        );
        $attributeTransformer = new AcquisitionAttributeFilloutTransformer();

        /** @var AcquisitionAttribute $attribute */
        foreach ($attributes as $attribute) {
            $bid              = $attribute->getBid();

            $attributeOptions = $attribute->getFieldOptions();
            if ($attribute->getFieldTypeChoiceType()) {
                $attributeOptions['empty_data'] = [];
            }

            try {
                if (isset($options['data']) && $options['data'] instanceof Participation) {
                    /** @var AcquisitionAttributeFillout $fillout */
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
            $builder->get($attribute->getName())->addModelTransformer($attributeTransformer);
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::ACQUISITION_FIELD_PUBLIC);
        $resolver->setAllowedTypes(self::ACQUISITION_FIELD_PUBLIC, 'bool');

        $resolver->setRequired(self::ACQUISITION_FIELD_PRIVATE);
        $resolver->setAllowedTypes(self::ACQUISITION_FIELD_PRIVATE, 'bool');

        $resolver->setDefaults(
            [
                'data_class'         => 'AppBundle\Entity\Participation',
                'cascade_validation' => true,
            ]
        );
    }
}
