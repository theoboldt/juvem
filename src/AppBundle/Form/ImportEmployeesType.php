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


use AppBundle\Controller\Event\EmployeeImportDto;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportEmployeesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'employees',
                CollectionType::class,
                [
                    'label'         => false,
                    'entry_type'    => ImportEmployeeType::class,
                    'allow_add'     => true,
                    'allow_delete'  => true,
                    'required'      => true,
                    'entry_options' => [
                        EmployeeType::ACQUISITION_FIELD_PUBLIC  => true,
                        EmployeeType::ACQUISITION_FIELD_PRIVATE => true,
                        EmployeeType::EVENT_OPTION              => $options[EmployeeType::EVENT_OPTION]
                    ],
                    'empty_data'    => new Employee($options[EmployeeType::EVENT_OPTION])
                ]
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(EmployeeType::EVENT_OPTION);
        $resolver->setAllowedTypes(EmployeeType::EVENT_OPTION, Event::class);
        
        $resolver->setDefaults(
            [
                'data_class'         => EmployeeImportDto::class,
                'cascade_validation' => true,
            ]
        );
    }
    
}