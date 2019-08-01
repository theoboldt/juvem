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

use AppBundle\Entity\AttendanceList\AttendanceListColumn;
use AppBundle\Entity\AttendanceList\AttendanceListColumnChoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttendanceListColumnChoiceType extends AbstractType
{
    
    const COLUMN_FIELD = 'column';
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'label'    => 'Titel',
                    'attr'     => ['aria-describedby' => 'help-info-choice-option-title'],
                    'required' => true,
                ]
            )->add(
                'shortTitle',
                TextType::class,
                [
                    'label'    => 'Internes Kürzel',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-info-choice-option-short'],
                ]
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::COLUMN_FIELD);
        $resolver->setAllowedTypes(self::COLUMN_FIELD, AttendanceListColumn::class);
        
        $resolver->setDefaults(
            [
                'data_class' => AttendanceListColumnChoice::class,
                'empty_data' => function (FormInterface $form) {
                    $column = $form->getConfig()->getOption(self::COLUMN_FIELD);
                    $choice = new AttendanceListColumnChoice('', '', $column);
                    return $choice;
                },
            ]
        );
    }
}
