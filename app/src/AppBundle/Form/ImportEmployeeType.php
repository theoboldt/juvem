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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class ImportEmployeeType extends EmployeeType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        
        $builder->add(
            'predecessor',
            EntityType::class,
            [
                'label'        => 'Vorgänger',
                'choice_label' => 'gid',
                'class'        => Employee::class,
                'multiple'     => false,
                'required'     => false
            ]
        );
    }
}
