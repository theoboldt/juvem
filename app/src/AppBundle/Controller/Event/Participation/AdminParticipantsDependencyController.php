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
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\CustomField\GroupCustomFieldValue;
use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\GroupType;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\InvalidTokenHttpException;
use AppBundle\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminParticipantsDependencyController extends AbstractController
{
    /**
     * Apply changes to multiple participants
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("participant", class="AppBundle:Participant", options={"id" = "aid"})
     * @Route("/admin/event/{eid}/dependencies/change_group_assignment/a{aid}", requirements={"eid": "\d+", "aid": "\d+"}, methods={"POST"})
     * @Security("is_granted('participants_edit', event)")
     * @param Event $event
     * @param Participant $participant
     * @param Request $request
     * @return JsonResponse
     */
    public function changeGroupAssignmentAction(Event $event, Participant $participant, Request $request)
    {
        $token    = $request->get('_token');
        $bid      = (int)$request->get('bid');
        $choiceId = (int)$request->get('choiceId');

        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('detecting' . $event->getEid())) {
            throw new InvalidTokenHttpException();
        }

        $customFields = $participant->getEvent()->getAcquisitionAttributes(false, true, false, true, true);
        if (!count($customFields)) {
            throw new \InvalidArgumentException('Event does not have custom fields assigned');
        }
        $customFieldValueContainer = null;
        foreach ($customFields as $customField) {
            if ($customField->getBid() === $bid) {
                $customFieldValueContainer = $participant->getCustomFieldValues()->getByCustomField($customField);
                break;
            }
        }
        if (!$customFieldValueContainer) {
            throw new \InvalidArgumentException('Custom field value container not found');
        }
        $customFieldValue = $customFieldValueContainer->getValue();
        if (!$customFieldValue instanceof GroupCustomFieldValue) {
            throw new \InvalidArgumentException('Invalid custom field value provided');
        }
        $em = $this->getDoctrine()->getManager();

        $choices = $customField->getChoiceOptions();
        /** @var AttributeChoiceOption $choice */
        foreach ($choices as $choice) {
            if ($choiceId === $choice->getId()) {
                $customFieldValue->setValue($choiceId);
                $em->persist($participant);
                $em->flush();
                return new JsonResponse(['success' => true]);
            }
        }

        return new JsonResponse(['success' => false]);
    }

    /**
     * Get details for detecting fields
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/dependencies", requirements={"eid": "\d+"}, name="event_admin_dependencies_overview")
     * @Security("is_granted('participants_read', event)")
     * @param Event     $event
     * @return Response
     */
    public function participantDetectingOverviewAction(Event $event)
    {
        $attributes = array_filter(
            $event->getAcquisitionAttributes(false, true, true, true, true),
            function ($attribute) {
                if (!$attribute || ($attribute instanceof Attribute && $attribute->getDeletedAt())) {
                    return false;
                }
                return true;
            }
        );

        $participationRepository     = $this->getDoctrine()->getRepository(Participation::class);
        $participants                = [];
        $participantsByParticipation = [];
        /** @var Participant $participant */
        foreach ($participationRepository->participantsList($event, null, true, true) as $participant) {
            $participants[$participant->getId()] = $participant;
            $participantsByParticipation[$participant->getParticipation()->getId()][$participant->getId()] = $participant;
        }

        $yearsOfLifeCount = [];
        /** @var Participant $participant */
        foreach ($participants as $participant) {
            if ($participant->getDeletedAt() || $participant->isRejected() || $participant->isWithdrawn()) {
                continue;
            }

            $yearsOfLife = $participant->getYearsOfLifeAtEvent();
            if (!isset($yearsOfLifeCount[$yearsOfLife])) {
                $yearsOfLifeCount[$yearsOfLife] = 0;
            }
            ++$yearsOfLifeCount[$yearsOfLife];
        }
        $yearsOfLifeParticipants = 0;
        $yearsOfLifeMaxShow      = 20;
        ksort($yearsOfLifeCount, SORT_NUMERIC);
        foreach (array_values($yearsOfLifeCount) as $index => $count) {
            $yearsOfLifeParticipants += $count;
            if ($yearsOfLifeParticipants > 70) {
                $yearsOfLifeMaxShow = $index;
                break;
            }
        }

        return $this->render(
            'event/admin/participant_detecting/event-detecting-overview.html.twig',
            [
                'event'              => $event,
                'statusFormatter'    => ParticipantStatus::formatter(),
                'participants'       => $participants,
                'attributes'         => $attributes,
                'yearsOfLife'        => array_keys($yearsOfLifeCount),
                'yearsOfLifeMaxShow' => $yearsOfLifeMaxShow,
            ]
        );
    }


    /**
     * Get detailed data for detecting fields
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/dependencies/data.json", requirements={"eid": "\d+"}, name="event_admin_dependencies_data")
     * @Security("is_granted('participants_read', event)")
     * @param Event     $event
     * @return Response
     */
    public function participantDetectingDataAction(Event $event): Response {
    $attributes = array_filter(
            $event->getAcquisitionAttributes(false, true, true, true, true),
            function ($attribute) {
                if (!$attribute || ($attribute instanceof Attribute && $attribute->getDeletedAt())) {
                    return false;
                }
                return true;
            }
        );

        $participationRepository     = $this->getDoctrine()->getRepository(Participation::class);
        $participants                = [];
        $participantsByParticipation = [];
        /** @var Participant $participant */
        foreach ($participationRepository->participantsList($event, null, true, true) as $participant) {
            $participants[$participant->getId()] = $participant;
            $participantsByParticipation[$participant->getParticipation()->getId()][$participant->getId()] = $participant;
        }

        $nodes                = [];
        $edges                = [];
        $participationEdges   = [];

        $attributeColors = [];
        $attributeOptionColors = [];
        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $bid             = $attribute->getBid();
            $attributeNumber = (hexdec(sha1($bid, true)) * hexdec(sha1($attribute->getFieldType(false), true)));
            while ($attributeNumber > 210) {
                $attributeNumber = array_sum(str_split($attributeNumber));
            }

            $attributeColor        = sprintf(
                'rgba(%1$d,%2$d,%3$d,0.9)',
                $attributeNumber,
                225 - $attributeNumber,
                ($attributeNumber+50) < 250 ? $attributeNumber+50 : $attributeNumber
            );
            $attributeColors[$bid] = $attributeColor;
            $attributeOptionColors[$bid] = [];

            $bid = $attribute->getBid();
            if ($attribute->getFieldType() === GroupType::class) {
                if ($attribute->getUseAtParticipant() || $attribute->getUseAtEmployee()) {
                    /** @var AttributeChoiceOption $choiceOption */
                    $choiceOptions = 0;
                    foreach ($attribute->getChoiceOptions() as $choiceOption) {
                        $attributeOptionColor = sprintf(
                            'rgba(%1$d,%2$d,%3$d,0.9)',
                            $attributeNumber + ($choiceOptions*6),
                            230 - $attributeNumber - ($choiceOptions*6),
                            ($attributeNumber+50) < 250 ? $attributeNumber+50 : $attributeNumber
                        );

                        $attributeOptionColors[$bid][$choiceOption->getId()] = $attributeOptionColor;

                        if ($choiceOption->getDeletedAt()) {
                            continue;
                        }

                        $groupTitle = sprintf(
                                '%s (<i>%s</i>)',
                                htmlspecialchars($choiceOption->getManagementTitle(true)),
                                htmlspecialchars($attribute->getManagementTitle())
                            );

                        $nodes[] = [
                            'id'         => self::choiceOptionNodeId($bid, $choiceOption->getId()),
                            'bid'        => $bid,
                            'choiceId'   => $choiceOption->getId(),
                            'type'       => 'choice',
                            'label'      => $choiceOption->getShortTitle(true),
                            'title'      => $groupTitle,
                            'shortTitle' => $choiceOption->getShortTitle(true),
                            'shape'      => 'circle',
                            'color'      => $attributeOptionColor,
                            'collapsed'  => false,
                        ];
                        ++$choiceOptions;
                    }
                }
            }
        }

        /** @var Participant $participant */
        foreach ($participants as $participant) {
            if ($participant->getDeletedAt() || $participant->isRejected() || $participant->isWithdrawn()) {
                continue;
            }

            $yearsOfLife = $participant->getYearsOfLifeAtEvent();

            switch ($participant->getGender()) {
                case Participant::LABEL_GENDER_FEMALE:
                case Participant::LABEL_GENDER_FEMALE_ALIKE:
                    $colorCode = '255,161,201';
                    break;
                case Participant::LABEL_GENDER_MALE:
                case Participant::LABEL_GENDER_MALE_ALIKE:
                    $colorCode = '85,159,255';
                    break;
                default:
                    $colorCode = '255,140,0';
                    break;
            }
            
            $color   = sprintf(
                'rgba(%s,%1.2f)',
                $colorCode,
                ($yearsOfLife / 18)
            );
            $nodes[] = [
                'id'          => self::participantNodeId($participant->getId()),
                'aid'         => $participant->getId(),
                'type'        => 'participant',
                'label'       => $participant->fullname() . ($yearsOfLife !== null ? ' (' . $yearsOfLife . ')' : ''),
                'shape'       => 'box',
                'color'       => $color,
                'gender'      => $participant->getGender(),
                'yearsOfLife' => $yearsOfLife,
                'age'         => $participant->getAgeAtEvent(),
                'confirmed'   => (int)$participant->isConfirmed(),
            ];

            /** @var Participant $relatedParticipant */
            foreach ($participantsByParticipation[$participant->getParticipation()->getId()] as $relatedParticipant) {
                if ($participant->getId() < $relatedParticipant->getId()) {
                    $edge = [
                        'from'   => self::participantNodeId($participant->getId()),
                        'to'     => self::participantNodeId($relatedParticipant->getId()),
                        'title'  => 'Teil derselben Anmeldung',
                        'arrows' => [
                            'to'   => ['enabled' => true, 'type' => 'bar'],
                            'from' => ['enabled' => true, 'type' => 'bar'],
                        ],
                        'type'   => 'participation',
                        'color'  => ['color' => 'rgba(7,80,142,0.8)'],
                    ];
                    $participationEdges[] = $edge;
                }
            }

            foreach ($attributes as $attribute) {
                $bid = $attribute->getBid();
                if ($attribute->getUseAtParticipant()) {
                    $customFieldValueContainer = $participant->getCustomFieldValues()->getByCustomField($attribute);
                    $customFieldValue = $customFieldValueContainer->getValue();
                } else {
                    continue;
                }
                
                if ($customFieldValue instanceof ParticipantDetectingCustomFieldValue) {
                    $selectedAid = $customFieldValue->getParticipantAid();
                    if ($selectedAid) {
                        $edge = [
                            'from'   => self::participantNodeId($participant->getId()),
                            'type'   => 'detecting',
                            'title'  => sprintf(
                                            '<i>%s</i> ist bei <i>%s</i> mit <i>%s</i>, <i>%s</i> verknüpft',
                                            htmlspecialchars($participant->getNameFirst()),
                                            htmlspecialchars($attribute->getManagementTitle()),
                                            htmlspecialchars($customFieldValue->getRelatedLastName()),
                                            htmlspecialchars($customFieldValue->getRelatedFirstName())
                                        ) . ($customFieldValue->isSystemSelection() ? ' (automatisch)' : ''),
                            'to'     => self::participantNodeId($selectedAid),
                            'arrows' => 'to',
                            'color'  => ['color' => $attributeColors[$bid]],
                        ];
                        if ($customFieldValue->isSystemSelection()) {
                            $edge['dashes'] = true;
                        }

                        $edges[] = $edge;
                    }
                } elseif ($customFieldValue instanceof GroupCustomFieldValue) {
                    $groupId = $customFieldValue->getValue();
                    if ($groupId) {
                        $selectedChoice = $attribute->getChoiceOption($groupId);
                        if (!isset($attributeOptionColors[$bid])) {
                            throw new \OutOfBoundsException($bid . ' is not in color list');
                        }
                        if (!isset($attributeOptionColors[$bid][$groupId])) {
                            throw new \OutOfBoundsException(
                                'Group ' . $groupId . ' not in color list of ' . $bid
                            );
                        } else {
                            $color = $attributeOptionColors[$bid][$groupId];
                        }

                        $edge    = [
                            'from'     => self::participantNodeId($participant->getId()),
                            'to'       => self::choiceOptionNodeId($bid, $groupId),
                            'type'     => 'choice',
                            'title'    => sprintf(
                                '<i>%s</i> ist bei <i>%s</i> eingeteilt in <i>%s</i>',
                                htmlspecialchars($participant->getNameFirst()),
                                htmlspecialchars($attribute->getManagementTitle()),
                                htmlspecialchars($selectedChoice->getManagementTitle(true))
                            ),
                            'bid'      => $bid,
                            'choiceId' => $groupId,
                            'color'    => ['color' => $color],
                        ];
                        $edges[] = $edge;
                    }
                }

            }
        }

        return new JsonResponse(
            [
                'nodes'              => $nodes,
                'edges'              => $edges,
                'participationEdges' => $participationEdges,
            ]
        );
    }

    /**
     * Generate node id for participant
     *
     * @param int $aid
     * @return string
     */
    private static function participantNodeId(int $aid): string
    {
        return 'a' . $aid;
    }

    /**
     * Generate node id for choice option
     *
     * @param int $bid
     * @param int $choiceId
     * @return string
     */
    private static function choiceOptionNodeId(int $bid, int $choiceId)
    {
        return 'b' . $bid . '-c' . $choiceId;
    }
}
