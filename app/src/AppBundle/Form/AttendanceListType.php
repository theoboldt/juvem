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

use AppBundle\Entity\AttendanceList\AttendanceList;
use AppBundle\Entity\AttendanceList\AttendanceListColumn;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttendanceListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titel'])
            ->add(
                'startDate',
                DateType::class,
                ['label'    => 'Datum',
                 'years'    => range(Date('Y') - 2, (int)Date('Y') + 2),
                 'format'   => 'dd.MM.yyyy',
                 'required' => false,
                ]
            )
            ->add(
                'columns',
                EntityType::class,
                [
                    'class'        => AttendanceListColumn::class,
                    'choice_label' => 'title',
                    'multiple'     => true,
                    'expanded'     => true,
                    'label'        => 'Spalten bei der Erfassung verwenden',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => AttendanceList::class,
            ]
        );
    }
}
