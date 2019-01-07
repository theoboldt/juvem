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
use AppBundle\Form\Transformer\AcquistionFormulaTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcquisitionChoiceOptionType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add(
                'formTitle',
                TextType::class,
                [
                    'label'    => 'Titel im Formular',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-info-choice-option-title']
                ]
            )->add(
                'managementTitle',
                TextType::class,
                [
                    'label'    => 'Interner Titel',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-info-choice-option-management']
                ]
            )->add(
                'shortTitle',
                TextType::class,
                [
                    'label'    => 'Internes Kürzel',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-info-choice-option-short']
                ]
            )->add(
                'priceFormula',
                TextType::class,
                [
                    'label'    => 'Formel, wenn diese Option ausgewählt ist',
                    'mapped'   => true,
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-form-formula'],
                ]
            );
            
        $builder->get('priceFormula')->addModelTransformer(new AcquistionFormulaTransformer());
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            [
                'data_class' => AttributeChoiceOption::class,
            ]
        );
    }
}
