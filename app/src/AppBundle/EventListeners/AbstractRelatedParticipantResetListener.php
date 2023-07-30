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


use AppBundle\Entity\CustomField\CustomFieldValueCollection;
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Manager\RelatedParticipantsLocker;
use Doctrine\ORM\EntityManager;

abstract class AbstractRelatedParticipantResetListener extends RelatedParticipantsLocker
{

    /**
     * Reset proposed participants for an complete event
     *
     * @param EntityManager $em                   Entity manage
     * @param Event $event                        Related event
     * @param int $maxWait                        If defined, specifies maximum time to wait for lock
     * @param Participant|null $updateParticipant If defined, all participant links relating to this participant are
     *                                            updated; Bad interface, should be improved later
     */
    protected function resetProposedParticipantsForEvent(
        EntityManager $em, Event $event, int $maxWait = 30, ?Participant $updateParticipant = null
    ): void
    {
        $lockHandle = $this->lock($event);
        if ($lockHandle !== false && flock($lockHandle, LOCK_EX)) {
            $em->getConnection()->beginTransaction();
            foreach (['participation' => 'pid', 'participant' => 'aid', 'employee' => 'gid'] as $table => $idColumn) {

                switch ($table) {
                    case 'participation':
                        $result = $em->getConnection()->executeQuery(
                            'SELECT p.pid AS id, p.custom_field_values
                   FROM participation p
                  WHERE p.custom_field_values IS NOT NULL
                    AND p.eid = ?',
                            [$event->getEid()]
                        );
                        break;
                    case 'participant':

                        $result = $em->getConnection()->executeQuery(
                            'SELECT a.aid AS id, a.custom_field_values
                   FROM participation p
             INNER JOIN participant a ON (a.pid = p.pid)
                  WHERE a.custom_field_values IS NOT NULL
                    AND p.eid = ?',
                            [$event->getEid()]
                        );
                        break;
                    case 'employee':
                        $result = $em->getConnection()->executeQuery(
                            'SELECT e.gid AS id, e.custom_field_values
                   FROM employee e
                  WHERE e.custom_field_values IS NOT NULL
                    AND e.eid = ?',
                            [$event->getEid()]
                        );
                        break;
                    default:
                        throw new \RuntimeException('Unknown table provided');
                }

                while ($row = $result->fetchAssociative()) {
                    $rowCustomFieldValues           = $row['custom_field_values'] ? json_decode(
                        $row['custom_field_values'], true
                    ) : [];
                    $collectionModified             = false;
                    $rowCustomFieldValuesCollection = CustomFieldValueCollection::createFromArray(
                        $rowCustomFieldValues
                    );
                    /** @var CustomFieldValueContainer $customFieldValueContainer */
                    foreach ($rowCustomFieldValuesCollection->getIterator() as $customFieldValueContainer) {
                        $customFieldValue = $customFieldValueContainer->getValue();
                        if ($customFieldValue instanceof ParticipantDetectingCustomFieldValue) {
                            $customFieldValue->setProposedParticipants(null);
                            $collectionModified = true;

                            if ($updateParticipant !== null
                                && $customFieldValue->getParticipantAid() === $updateParticipant->getAid()) {
                                $customFieldValue->setParticipantFirstName($updateParticipant->getNameFirst());
                                $customFieldValue->setParticipantLastName($updateParticipant->getNameLast());
                            }
                        }
                    }
                    if ($collectionModified) {
                        $em->getConnection()->executeStatement(
                            'UPDATE ' . $table .
                            ' SET custom_field_values = :custom_field_values WHERE ' . $idColumn . ' = :id',
                            [
                                'id'                  => $row['id'],
                                'custom_field_values' => json_encode($rowCustomFieldValuesCollection),
                            ]
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
