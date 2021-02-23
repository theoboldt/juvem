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

use AppBundle\Entity\Event;
use AppBundle\Entity\HumanTrait;
use AppBundle\Entity\User;
use AppBundle\Entity\UserRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function Doctrine\ORM\QueryBuilder;

class EventAddUserAssignmentsType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Event $event */
        $event = $options['event'];
        $eid   = $event->getEid();

        $builder
            ->add(
                'assignUser',
                EntityType::class,
                [
                    'label'         => 'Benutzer',
                    'class'         => User::class,
                    'query_builder' => function (UserRepository $r) use ($eid) {
                        $qb = $r->createQueryBuilder('u');
                        return $qb->andWhere(
                            $qb->expr()->orX(
                                $qb->expr()->like('u.roles', ':enablingRoleA'),
                                $qb->expr()->like('u.roles', ':enablingRoleB')
                            )
                        )
                                  ->setParameter('enablingRoleA', '%"ROLE_ADMIN_EVENT"%')
                                  ->setParameter('enablingRoleB', '%"ROLE_CLOUD"%')
                                  ->andWhere('u.enabled = 1')
                                  ->addOrderBy('u.nameLast', 'ASC')
                                  ->addOrderBy('u.nameFirst', 'ASC')
                                  ->leftJoin('u.eventAssignments', 'a', Join::WITH, 'a.event = ' . $eid)
                                  ->andWhere('a IS NULL');
                    },
                    'choice_label' => function (User $user) {
                        $roles = [];
                        if ($user->hasRole(User::ROLE_ADMIN_EVENT_GLOBAL)) {
                            $roles[] = User::ROLE_ADMIN_EVENT_GLOBAL_LABEL;
                        } elseif ($user->hasRole(User::ROLE_ADMIN_EVENT)) {
                            $roles[] = User::ROLE_ADMIN_EVENT_LABEL;
                        } elseif ($user->hasRole(User::ROLE_CLOUD)) {
                            $roles[] = User::ROLE_CLOUD_LABEL;
                        }
                        return $user->fullname() . ' [' . implode(', ', $roles) . ']';
                    },
                    'multiple'      => true,
                    'required'      => false,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('event');
        $resolver->setAllowedTypes('event', Event::class);
    }
}
