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

class RelatedParticipantResetListener extends RelatedParticipantsLocker
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
     * Persist participant
     *
     * @param Participant        $participant Related entity
     * @param LifecycleEventArgs $event       Doctrine information
     */
    public function prePersist(Participant $participant, LifecycleEventArgs $event)
    {
        //todo
    }

    /**
     * Reset proposed participants for an complete event
     *
     * @param EntityManager $em    Entity manage
     * @param Event         $event Related event
     */
    public function resetProposedParticipantsForEvent(EntityManager $em, Event $event)
    {
        $lockHandle = $this->lock($event);
        if ($lockHandle !== false && flock($lockHandle, LOCK_EX)) {
            $em->getConnection()->beginTransaction();
            $result = $em->getConnection()->executeQuery(
                'SELECT acquisition_attribute_fillout.oid, acquisition_attribute_fillout.value AS fillout_value 
                   FROM acquisition_attribute_fillout
             INNER JOIN acquisition_attribute ON (acquisition_attribute_fillout.bid = acquisition_attribute.bid)
             INNER JOIN event_acquisition_attribute ON (event_acquisition_attribute.bid = acquisition_attribute.bid)
                  WHERE acquisition_attribute.field_type = ?
                    AND event_acquisition_attribute.eid = ?',
                [ParticipantDetectingType::class, $event->getEid()]
            );
            while ($row = $result->fetch()) {
                $filloutValue = $row['fillout_value'];
                if ($filloutValue !== null) {
                    $filloutDecoded = json_decode($filloutValue, true);
                    if (is_array($filloutDecoded)
                        && isset($filloutDecoded[ParticipantFilloutValue::KEY_PROPOSED_IDS])) {
                        unset($filloutDecoded[ParticipantFilloutValue::KEY_PROPOSED_IDS]);
                        $em->getConnection()->executeUpdate(
                            'UPDATE acquisition_attribute_fillout SET value = ? WHERE oid = ?',
                            [json_encode($filloutDecoded), $row['oid']]
                        );
                    }
                }
            }
            $em->getConnection()->commit();
            $this->release($event, $lockHandle);
        } else {
            fclose($lockHandle);
            sleep(2);
            $this->resetProposedParticipantsForEvent($em, $event);
        }
    }
}
