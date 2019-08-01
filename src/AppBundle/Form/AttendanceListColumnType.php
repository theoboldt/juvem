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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class AttendanceListColumnType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \AppBundle\Entity\AttendanceList\AttendanceListColumn $column */
        $column = $options['data'];
        
        $builder->add(
            'title',
            TextType::class,
            [
                'label'    => 'Titel',
                'required' => true,
                'attr'     => ['aria-describedby' => 'help-management-title'],
            ]
        )->add(
            'choices',
            CollectionType::class,
            [
                'label'         => 'Optionen der Auswahl',
                'entry_type'    => AttendanceListColumnChoiceType::class,
                'by_reference'  => false,
                'allow_add'     => true,
                'allow_delete'  => true,
                'attr'          => ['aria-describedby' => 'help-choice-options'],
                'required'      => true,
                'entry_options' => [
                    AttendanceListColumnChoiceType::COLUMN_FIELD => $column,
                ]
            ]
        );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => AttendanceListColumn::class,
                'cascade_validation' => true,
            ]
        );
    }
}