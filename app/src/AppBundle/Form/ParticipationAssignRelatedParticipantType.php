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
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationAssignRelatedParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Event $event */
        $event = $options['event'];
        $eid   = $event->getEid();
        $builder
            ->add(
                'oid',
                HiddenType::class
            )
            ->add(
                'related',
                EntityType::class,
                [
                    'label'         => 'Verknüpfte:r Teilnehmer:in',
                    'placeholder'   => '(keiner)',
                    'class'         => Participant::class,
                    'query_builder' => function (EntityRepository $r) use ($eid) {
                        return $r->createQueryBuilder('a')
                                 ->andWhere('a.deletedAt IS NULL')
                                 ->innerJoin('a.participation', 'p', Join::ON)
                                 ->andWhere('p.event = ' . $eid)
                                 ->addOrderBy('a.nameLast', 'ASC')
                                 ->addOrderBy('a.nameFirst', 'ASC');
                    },
                    'choice_label'  => function (Participant $participant) use ($event) {
                        return sprintf(
                            '%s (%d)',
                            $participant->fullname(),
                            EventRepository::yearsOfLife($participant->getBirthday(), $event->getStartDate())
                        );
                    },
                    'multiple'      => false,
                    'required'      => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('event');
        $resolver->setAllowedTypes('event', Event::class);
    }
}
