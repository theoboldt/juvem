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


use AppBundle\Entity\AcquisitionAttribute\ParticipantFilloutValue;
use AppBundle\Entity\Event;
use AppBundle\Form\ParticipantDetectingType;
use AppBundle\Manager\RelatedParticipantsLocker;
use Doctrine\ORM\EntityManager;

abstract class AbstractRelatedParticipantResetListener extends RelatedParticipantsLocker
{
    
    /**
     * Reset proposed participants for an complete event
     *
     * @param EntityManager $em Entity manage
     * @param Event $event Related event
     * @param int $maxWait If defined, specifies maximum time to wait for lock
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function resetProposedParticipantsForEvent(EntityManager $em, Event $event, int $maxWait = 30)
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
                        $em->getConnection()->executeStatement(
                            'UPDATE acquisition_attribute_fillout SET value = ? WHERE oid = ?',
                            [json_encode($filloutDecoded), $row['oid']]
                        );
                    }
                }
            }
            $em->getConnection()->commit();
            $this->release($event, $lockHandle);
        } else {
            
            $this->closeLockHandle($lockHandle);
            $sleep = 2;
            sleep($sleep);
            $maxWait -= $sleep;
            if ($maxWait <= 0) {
                throw new \RuntimeException('Failed to get lock');
            }
            $this->resetProposedParticipantsForEvent($em, $event, $maxWait);
        }
    }
}
