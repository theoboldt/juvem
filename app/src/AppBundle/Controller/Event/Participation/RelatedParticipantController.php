<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event\Participation;

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventAcquisitionAttributeUnavailableException;
use AppBundle\Entity\EventRelatedEntity;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\Manager\RelatedParticipantsFinder;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RelatedParticipantController
{
    use DoctrineAwareControllerTrait;

    /**
     * @var RelatedParticipantsFinder
     */
    private RelatedParticipantsFinder $relatedParticipantsFinder;

    /**
     * @param ManagerRegistry           $doctrine
     * @param RelatedParticipantsFinder $relatedParticipantsFinder
     */
    public function __construct(
        ManagerRegistry           $doctrine,
        RelatedParticipantsFinder $relatedParticipantsFinder
    ) {
        $this->doctrine                  = $doctrine;
        $this->relatedParticipantsFinder = $relatedParticipantsFinder;
    }


    /**
     * Get proposal
     *
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/participant_proposals", requirements={"eid": "\d+"},
     *                                                    name="admin_event_participant_proposals")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('participants_read', event)")
     */
    public function relatedParticipantProposalAction(Event $event, Request $request)
    {
        $entityClass = $request->get('entityClass');
        $entityId    = (int)$request->get('entityId');
        $bid         = (int)$request->get('bid');

        switch ($entityClass) {
            case Participant::class:
                $repository = $this->getDoctrine()->getRepository(Participant::class);
                $entity     = $repository->findOneBy(['aid' => $entityId]);
                break;
            case Participation::class:
                $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
                $entity                  = $participationRepository->findDetailed($entityId);
                break;
            case Employee::class:
                $repository = $this->getDoctrine()->getRepository(Employee::class);
                $entity     = $repository->findOneBy(['gid' => $entityId]);
                break;
            default:
                return new JsonResponse(
                    [
                        'success' => false,
                        'message' => 'Für <i>' . $entityClass . '</i> können keine Vorschläge gefunden werden',
                    ]
                );
        }
        if (!$entity instanceof EventRelatedEntity) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Da <i>' . $entityClass . ':' . $entityId .
                                 '</i> nicht zu einer Veranstaltung gehört, können keine Vorschläge gefunden werden',
                ]
            );
        }
        if (!$entity instanceof EntityHavingCustomFieldValueInterface) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Da <i>' . $entityClass . ':' . $entityId .
                                 '</i> keine Felder zugewiesen hat, können keine Vorschläge gefunden werden',
                ]
            );
        }
        if ($entity->getEvent()->getEid() !== $event->getEid()) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Da <i>' . $entityClass . ':' . $entityId .
                                 '</i> zu einer anderen Veranstaltung gehört als erwartet, können keine Vorschläge gefunden werden',
                ]
            );
        }
        
        try {
        $customField = $event->getAcquisitionAttribute($bid);
        } catch (EventAcquisitionAttributeUnavailableException $e) {
            throw new BadRequestHttpException('Event '.$event->getEid().' does not have attribute '.$bid.' assigned', $e);
        }
        
        $customFieldValueContainer = $entity->getCustomFieldValues()->get($bid, false);
        $customFieldValue          = $customFieldValueContainer ? $customFieldValueContainer->getValue() : null;


        if ($customFieldValue !== null && !$customFieldValue instanceof ParticipantDetectingCustomFieldValue) {
            throw new BadRequestHttpException(
                'Custom field value of ' . $entityClass . ':' . $entityId . ' for bid ' . $bid . ' must be ' .
                ParticipantDetectingCustomFieldValue::class
            );
        }


        /** @var RelatedParticipantsFinder $repository */
        $finder       = $this->relatedParticipantsFinder;
        $participants = $finder->proposedParticipants($customFieldValue, $customField, $event);

        $statusFormatter = ParticipantStatus::formatter();

        $result = [];
        foreach ($participants as $participant) {
            $participantStatusText = $statusFormatter->formatMask($participant->getStatus(true));
            if ($participant->getDeletedAt()) {
                $participantStatusText .= ' <span class="label label-danger">gelöscht</span>';
            }

            $isSelected = $participant->getAid() === (int)$customFieldValue->getParticipantAid();

            $result[] = [
                'aid'       => $participant->getAid(),
                'firstName' => $participant->getNameFirst(),
                'lastName'  => $participant->getNameLast(),
                'age'       => $participant->getYearsOfLifeAtEvent(),
                'status'    => $participantStatusText,
                'selected'  => $isSelected,
                'system'    => $isSelected && $customFieldValue->isSystemSelection(),
            ];
        }

        return new JsonResponse(['rows' => $result, 'success' => true]);
    }

}
