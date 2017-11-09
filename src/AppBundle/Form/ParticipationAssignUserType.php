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

use AppBundle\Entity\HumanTrait;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Entity\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationAssignUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'assignedUser',
                EntityType::class,
                [
                    'label'         => 'Verknüpftes Benutzerkonto',
                    'class'         => User::class,
                    'query_builder' => function (UserRepository $r) {
                        return $r->createQueryBuilder('u')
                                 ->andWhere('u.enabled = 1')
                                 ->addOrderBy('u.nameLast', 'ASC')
                                 ->addOrderBy('u.nameFirst', 'ASC');
                    },
                    'choice_label'  => function (User $user) {
                        return HumanTrait::fullname($user->getNameLast(), $user->getNameFirst());
                    },
                    'multiple'      => false,
                    'required'      => false
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Participation::class,
            ]
        );
    }
}
