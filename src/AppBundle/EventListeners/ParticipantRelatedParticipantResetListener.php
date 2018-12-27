<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\EventListeners;

use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\ParticipantFilloutValue;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Form\ParticipantDetectingType;
use AppBundle\Manager\RelatedParticipantsLocker;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class ParticipantRelatedParticipantResetListener extends AbstractRelatedParticipantResetListener
{

    /**
     * Update participant
     *
     * @param Participant        $participant Related entity
     * @param PreUpdateEventArgs $event       Doctrine information
     */
    public function preUpdate(Participant $participant, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('nameLast') || $event->hasChangedField('nameFirst')) {
            $this->resetProposedParticipantsForEvent(
                $event->getEntityManager(), $participant->getParticipation()->getEvent()
            );
        }
    }

    /**
     * Persist new participant
     *
     * @param Participant        $participant Related entity
     * @param LifecycleEventArgs $event       Doctrine information
     */
    public function prePersist(Participant $participant, LifecycleEventArgs $event)
    {
        if ($participant->getAid()) {
            $this->resetProposedParticipantsForEvent(
                $event->getEntityManager(), $participant->getParticipation()->getEvent()
            );
        }
    }
    
}
