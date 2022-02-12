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

use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Participation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoveParticipationEventType extends AbstractType
{
    
    const PARTICIPATION_OPTION = 'participation';
    
    const PARAM_EVENT_OLD = '{EVENT_OLD}';

    const PARAM_PID_OLD = '{PID_OLD}';
    
    const PARAM_EVENT_NEW = '{EVENT_NEW}';
    
    const PARAM_PID_NEW = '{PID_NEW}';
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $commentOld    = 'Diese Anmeldung wurde verschoben in die Veranstaltung "{EVENT_NEW}". Die neue Anmeldung hat die Nummer #{PID_NEW}.';
        $commentNew    = 'Diese Anmeldung ist verschoben worden von der Veranstaltung "{EVENT_OLD}". Die alte Anmeldung hatte die Nummer #{PID_OLD}.';
        $participation = null;
        if ($options[self::PARTICIPATION_OPTION]) {
            /** @var Participation $participation */
            $participation = $options[self::PARTICIPATION_OPTION];
        }
        
        $builder
            ->add(
                'targetEvent',
                EntityType::class,
                [
                    'label' => 'Ziel-Veranstaltung',
                    'class' => 'AppBundle:Event',
                    'query_builder' => function (EventRepository $er) use ($participation) {
                        $builder = $er->createQueryBuilder('e')
                                      ->andWhere('e.isActive = 1')
                                      ->andWhere('e.deletedAt IS NULL')
                                      ->orderBy('e.title', 'ASC');
                        if ($participation) {
                            $builder->andWhere($builder->expr()->neq('e.eid', $participation->getEvent()->getEid()));
                        }
                        return $builder;
                    },
                    'choice_label' => 'title',
                    'multiple' => false,
                    'required' => true
                ]
            )
            ->add(
                'commentOldParticipation', TextareaType::class,
                ['label' => 'Kommentar für originale Anmeldung', 'data' => $commentOld]
            )
            ->add(
                'commentNewParticipation', TextareaType::class,
                ['label' => 'Kommentar für neue Anmeldung', 'data' => $commentNew]
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(self::PARTICIPATION_OPTION, null);
        $resolver->setAllowedTypes(self::PARTICIPATION_OPTION, Participation::class);
    }
}
