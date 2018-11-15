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
use AppBundle\Entity\User;
use AppBundle\Entity\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeAssignUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'assignedUser',
                EntityType::class,
                [
                    'label'         => 'Verknüpftes Benutzerkonto',
                    'placeholder'   => '(keines)',
                    'class'         => User::class,
                    'query_builder' => function (UserRepository $r) {
                        $qb = $r->createQueryBuilder('u');
                        $qb->andWhere('u.enabled = 1')
                           ->andWhere($qb->expr()->like('u.roles', '\'%"'.User::ROLE_EMPLOYEE.'"%\''))
                           ->addOrderBy('u.nameLast', 'ASC')
                           ->addOrderBy('u.nameFirst', 'ASC');
                        return $qb;
                    },
                    'choice_label'  => 'fullname',
                    'multiple'      => false,
                    'required'      => false,
                ]
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Employee::class,
            ]
        );
    }
}
