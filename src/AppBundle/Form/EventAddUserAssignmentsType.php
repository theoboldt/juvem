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
use Doctrine\ORM\PersistentCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventAddUserAssignmentsType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'assignUser',
                EntityType::class,
                [
                    'label'         => 'Benutzer',
                    'class'         => User::class,
                    'query_builder' => function (UserRepository $r) {
                        $qb = $r->createQueryBuilder('u');
                        return $qb->andWhere($qb->expr()->like('u.roles', ':requiredRole'))
                                  ->setParameter('requiredRole', '%"ROLE_ADMIN_EVENT"%')
                                  ->andWhere('u.enabled = 1')
                                  ->addOrderBy('u.nameLast', 'ASC')
                                  ->addOrderBy('u.nameFirst', 'ASC')
                                  ->leftJoin('u.eventAssignments', 'a')
                                  ->groupBy('u.id')
                                  ->andWhere('a IS NULL');
                    },
                    'choice_label'  => function (User $user) {
                        return HumanTrait::fullname($user->getNameLast(), $user->getNameFirst());
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
