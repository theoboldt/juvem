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


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\GroupFilloutValue;
use AppBundle\Entity\AcquisitionAttribute\ParticipantFilloutValue;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\GroupType;
use AppBundle\Form\ParticipantDetectingType;
use AppBundle\InvalidTokenHttpException;
use AppBundle\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminParticipantsDependencyController extends Controller
{
    /**
     * Apply changes to multiple participants
     *
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
        $fillout = $participant->getAcquisitionAttributeFillout($bid, true);
        $value = $fillout->getValue();
        if (!$value instanceof GroupFilloutValue) {
            throw new \InvalidArgumentException('Invalid fillout value provided');
        }
    
        $em = $this->getDoctrine()->getManager();
    
        $choices = $fillout->getAttribute()->getChoiceOptions();
        /** @var AttributeChoiceOption $choice */
        foreach ($choices as $choice) {
            if ($choiceId === $choice->getId()) {
                $value = GroupFilloutValue::createForChoiceOption($choice);
                $fillout->setValue($value->getRawValue());
                $em->persist($fillout);
                $em->flush();
                return new JsonResponse(['success' => true]);
            }
        }
    
        return new JsonResponse(['success' => false]);
    }
    
    /**
     * Get details for detecting fields
     *
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

        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participants            = [];
        /** @var Participant $participant */
        foreach ($participationRepository->participantsList($event, null, true, true) as $participant) {
            $participants[$participant->getId()] = $participant;
        }

        $yearsOfLifeAvailable = [];
        $nodes                = [];
        $edges                = [];

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
                        if ($choiceOption->getDeletedAt()) {
                            continue;
                        }
                        $attributeOptionColor = sprintf(
                            'rgba(%1$d,%2$d,%3$d,0.9)',
                            $attributeNumber + ($choiceOptions*6),
                            230 - $attributeNumber - ($choiceOptions*6),
                            ($attributeNumber+50) < 250 ? $attributeNumber+50 : $attributeNumber
                        );

                        $attributeOptionColors[$bid][$choiceOption->getId()] = $attributeOptionColor;
    
                        $nodes[] = [
                            'id'        => self::choiceOptionNodeId($bid, $choiceOption->getId()),
                            'bid'       => $bid,
                            'choiceId'  => $choiceOption->getId(),
                            'type'      => 'choice',
                            'label'     => $choiceOption->getShortTitle(true),
                            'title'     => sprintf(
                                '%s (<i>%s</i>)',
                                htmlspecialchars($choiceOption->getManagementTitle(true)),
                                htmlspecialchars($attribute->getManagementTitle())
                            ),
                            'shape'     => 'circle',
                            'color'     => $attributeOptionColor,
                            'collapsed' => false,
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

            $yearsOfLife                        = $participant->getYearsOfLifeAtEvent();
            $yearsOfLifeAvailable[$yearsOfLife] = $yearsOfLife;

            $color   = sprintf(
                'rgba(%s,%1.2f)',
                $participant->getGender(false) ===
                Participant::TYPE_GENDER_FEMALE ? '255,161,201' : '85,159,255',
                ($yearsOfLife / 18)
            );
            $nodes[] = [
                'id'          => self::participantNodeId($participant->getId()),
                'aid'         => $participant->getId(),
                'type'        => 'participant',
                'label'       => $participant->fullname() . ' (' . $yearsOfLife . ')',
                'shape'       => 'box',
                'color'       => $color,
                'gender'      => $participant->getGender(false),
                'yearsOfLife' => $yearsOfLife,
            ];

            foreach ($attributes as $attribute) {
                $bid = $attribute->getBid();
                if ($attribute->getUseAtParticipant()) {
                    $fillout = $participant->getAcquisitionAttributeFillout($bid, true);
                    $value   = $fillout->getValue();
                } else {
                    continue;
                }
                switch ($attribute->getFieldType()) {
                    case ParticipantDetectingType::class:
                        /** @var ParticipantFilloutValue $value */

                        $selectedAid = $value->getSelectedParticipantId();
                        if ($selectedAid) {
                            $edge = [
                                'from'   => self::participantNodeId($participant->getId()),
                                'title'  => sprintf(
                                    '<i>%s</i> ist bei <i>%s</i> mit <i>%s</i> verknüpft',
                                    htmlspecialchars($participant->getNameFirst()),
                                    htmlspecialchars($attribute->getManagementTitle()),
                                    htmlspecialchars($value->getRelatedFirstName())
                                    ),
                                'to'     => self::participantNodeId($selectedAid),
                                'arrows' => 'to',
                                'color'  => ['color' => $attributeColors[$bid]],
                            ];
                            if ($value->isSystemSelection()) {
                                $edge['dashes'] = true;
                            }

                            $edges[] = $edge;
                        }
                        break;
                    case GroupType::class:
                        /** @var GroupFilloutValue $value */
                        $groupId = $value->getGroupId();
                        if ($groupId) {
                            /** @var AttributeChoiceOption $selectedChoice */
                            $selectedChoice = $value->getSelectedChoices();
                            $selectedChoice = reset($selectedChoice);
            
                            $edge    = [
                                'from'     => self::participantNodeId($participant->getId()),
                                'to'       => self::choiceOptionNodeId($bid, $groupId),
                                'title'    => sprintf(
                                    '<i>%s</i> ist bei <i>%s</i> eingeteilt in <i>%s</i>',
                                    htmlspecialchars($participant->getNameFirst()),
                                    htmlspecialchars($attribute->getManagementTitle()),
                                    htmlspecialchars($selectedChoice->getManagementTitle(true))
                                ),
                                'bid'      => $bid,
                                'choiceId' => $groupId,
                                'color'    => ['color' => $attributeOptionColors[$bid][$groupId]],
                            ];
                            $edges[] = $edge;
                        }
                        break;
                }
            }
        }

        $yearsOfLifeAvailable = array_values($yearsOfLifeAvailable);
        sort($yearsOfLifeAvailable);

        return $this->render(
            'event/admin/participant_detecting/event-detecting-overview.html.twig',
            [
                'event'       => $event,
                'attributes'  => $attributes,
                'nodes'       => $nodes,
                'edges'       => $edges,
                'yearsOfLife' => $yearsOfLifeAvailable,
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
