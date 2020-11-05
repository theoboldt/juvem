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

use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class UserRoleAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
    
        $builder
            ->add('uid', HiddenType::class)
            ->add(
                'role',
                ChoiceType::class,
                [
                    'label'    => 'Rollen',
                    'choices'  => [
                        User::ROLE_ADMIN_LABEL              => User::ROLE_ADMIN,
                        User::ROLE_ADMIN_EVENT_LABEL        => User::ROLE_ADMIN_EVENT,
                        User::ROLE_ADMIN_EVENT_GLOBAL_LABEL => User::ROLE_ADMIN_EVENT_GLOBAL,
                        User::ROLE_ADMIN_USER_LABEL         => User::ROLE_ADMIN_USER,
                        User::ROLE_ADMIN_NEWSLETTER_LABEL   => User::ROLE_ADMIN_NEWSLETTER,
                        User::ROLE_EMPLOYEE_LABEL           => User::ROLE_EMPLOYEE,
                    ],
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false
                ]
            );
    }
    
    public function getBlockPrefix()
    {
        return 'app_bundle_user_role_assignment';
    }

}
