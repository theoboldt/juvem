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
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationRepository;
use AppBundle\Twig\Extension\ParticipationsParticipantsNamesGrouped;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoveParticipationParticipationType extends AbstractType
{

    const PARTICIPATION_OPTION = 'participation';

    const PARAM_PID_OLD = '{PID_OLD}';

    const PARAM_PID_NEW = '{PID_NEW}';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $commentOld = 'Die Teilnehmer:in wurde verschoben in die Anmeldung mit der Nummer #{PID_NEW}.';
        $commentNew
                    = 'Die Teilnehmer:in ist von einer anderen Anmeldung verschoben worden. Die alte Anmeldung hatte die Nummer #{PID_OLD}.';

        /** @var Participation $participation */
        $participation = $options[self::PARTICIPATION_OPTION];
        /** @var Event $event */
        $event = $participation->getEvent();

        $builder
            ->add(
                'targetParticipation',
                EntityType::class,
                [
                    'label'         => 'Ziel-Anmeldung',
                    'class'         => 'AppBundle:Participation',
                    'query_builder' => function (ParticipationRepository $er) use ($participation, $event) {
                        $builder = $er->createQueryBuilder('p')
                                      ->leftJoin('p.participants', 'a', Join::WITH)
                                      ->andWhere('p.deletedAt IS NULL')
                                      ->addOrderBy('p.nameLast', 'ASC')
                                      ->addOrderBy('p.nameFirst', 'ASC');

                        $builder->andWhere($builder->expr()->neq('p.pid', $participation->getPid()));
                        $builder->andWhere($builder->expr()->eq('p.event', $participation->getEvent()->getEid()));

                        return $builder;
                    },
                    'choice_label'  => function (Participation $p) {
                        $label        = $p->fullname() . ' ';
                        $participants = [];
                        foreach ($p->getParticipants() as $participant) {
                            if (!$participant->isWithdrawn()
                                && !$participant->isRejected()
                                && !$participant->isDeleted()) {
                                $participants[] = $participant;
                            }
                        } //foreach
                        if (count($participants)) {
                            $label .= '[' . ParticipationsParticipantsNamesGrouped::combineNames($participants) . ']';
                        } else {
                            $label .= '[Keine aktiven Teilnehmer:innen]';
                        }

                        return $label;
                    },
                    'multiple'      => false,
                    'required'      => true,
                ]
            )
            ->add(
                'commentOld', TextareaType::class,
                ['label' => 'Kommentar für originale Anmeldung', 'data' => $commentOld]
            )
            ->add(
                'commentNew', TextareaType::class,
                ['label' => 'Kommentar für neue Anmeldung', 'data' => $commentNew]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::PARTICIPATION_OPTION);
        $resolver->setAllowedTypes(self::PARTICIPATION_OPTION, Participation::class);
    }
}
