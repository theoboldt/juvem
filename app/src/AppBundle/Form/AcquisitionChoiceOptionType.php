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

use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcquisitionChoiceOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data     = isset($options['data']) ? $options['data'] : null;
        $isSystem = $data instanceof AttributeChoiceOption && $data->isSystem();

        $builder
            ->add(
                'formTitle',
                TextType::class,
                [
                    'label'    => 'Titel im Formular',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-info-choice-option-title'],
                ]
            );

        if (!$isSystem) {
            $builder
                ->add(
                    'managementTitle',
                    TextType::class,
                    [
                        'label'    => 'Interner Titel',
                        'required' => false,
                        'attr'     => ['aria-describedby' => 'help-info-choice-option-management'],
                    ]
                );
            $builder
                ->add(
                    'shortTitle',
                    TextType::class,
                    [
                        'label'    => 'Internes Kürzel',
                        'required' => false,
                        'attr'     => ['aria-describedby' => 'help-info-choice-option-short'],
                    ]
                );
        }
        
        $builder
            ->add(
                'formDescription',
                TextareaType::class,
                [
                    'label'    => 'Erläuternde Beschreibung im Formular',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-info-choice-option-form-description'],
                ]
            );

        if ($isSystem) {
            $builder
                ->add(
                    'isArchived',
                    CheckboxType::class,
                    [
                        'label'    => 'Option als archiviert behandeln. Wurde die Option gewählt, bleibt diese Einstellung erhalten. Sie kann in Formularen jedoch nicht mehr ausgewählt werden.',
                        'required' => false,
                    ]
                );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => AttributeChoiceOption::class,
            ]
        );
    }
}
