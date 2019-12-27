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

use AppBundle\Entity\Participation;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class ParticipationRelatedParticipantResetListener extends AbstractRelatedParticipantResetListener
{
    
    /**
     * Update @see Participation
     *
     * @param Participation $participation Related entity
     * @param PreUpdateEventArgs $event Doctrine information
     */
    public function preUpdate(Participation $participation, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('deletedAt')) {
            $this->resetProposedParticipantsForEvent(
                $event->getEntityManager(), $participation->getEvent()
            );
        }
    }
    
}
