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

use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeType extends AbstractType
{
    use AcquisitionAttributeIncludingTypeTrait;

    const EVENT_OPTION = 'event';

    const ACQUISITION_FIELD_PUBLIC = 'acquisitionFieldPublic';

    const ACQUISITION_FIELD_PRIVATE = 'acquisitionFieldPrivate';


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateTypeOptions = [
            'years'  => range(Date('Y') - 1, Date('Y') + 1),
            'format' => 'dd.M.yyyy',
        ];
        $hasDateCheckbox = [
            'required'   => false,
            'attr'       => ['class' => 'checkbox-smart'],
            'label_attr' => ['class' => 'control-label'],
        ];
    
        if (isset($options[self::EVENT_OPTION])) {
            $event = $options[self::EVENT_OPTION];
        } else {
            /** @var Employee $employee */
            $employee = $options['data'];
            $event    = $employee->getEvent();
        }
    
        $builder
            ->add(
                'salutation',
                ChoiceType::class,
                [
                    'label'    => 'Anrede',
                    'choices'  => ['Frau' => 'Frau', 'Herr' => 'Herr'],
                    'expanded' => false,
                    'required' => true,
                ]
            )
            ->add('nameFirst', TextType::class, ['label' => 'Vorname', 'required' => true])
            ->add('nameLast', TextType::class, ['label' => 'Nachname', 'required' => true])
            ->add('addressStreet', TextType::class, ['label' => 'Straße u. Hausnummer'])
            ->add('addressZip', TextType::class, ['label' => 'Postleitzahl'])
            ->add('addressCity', TextType::class, ['label' => 'Stadt'])
            ->add(
                'addressCountry',
                TextType::class,
                [
                    'label'    => 'Land',
                    'required' => true
                ]
            )
            ->add('email', EmailType::class, ['label' => 'E-Mail'])
            ->add(
                'phoneNumbers',
                CollectionType::class,
                [
                    'label'        => false,
                    'entry_type'   => PhoneNumberType::class,
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'attr'         => ['aria-describedby' => 'help-info-phone-numbers'],
                    'required'     => true,
                ]

            );

        $attributes = $event->getAcquisitionAttributes(
            false, false, true, $options[self::ACQUISITION_FIELD_PRIVATE], $options[self::ACQUISITION_FIELD_PUBLIC]
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

        $resolver->setDefault(self::EVENT_OPTION, null);
        $resolver->setAllowedTypes(self::EVENT_OPTION, [Event::class, 'null']);
    
        $resolver->setDefaults(
            [
                'data_class'         => Employee::class,
                'cascade_validation' => true,
                'empty_data'         => function (FormInterface $form) {
                    $event = null;
                    if ($form->getConfig()->hasOption(self::EVENT_OPTION)) {
                        $event = $form->getConfig()->getOption(self::EVENT_OPTION);
                    }
                    return new Employee($event);
                },
            ]
        );
    }
}
