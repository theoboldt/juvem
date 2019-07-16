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
use AppBundle\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminParticipantDetectingController extends Controller
{
    /**
     * Get details for detecting fields
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/detectings", requirements={"eid": "\d+"}, name="event_admin_detectings_overview")
     * @param Event     $event
     * @param Attribute $attribute
     * @return Response
     */
    public function participantDetectingOverviewAction(Event $event)
    {
        $attributes = $event->getAcquisitionAttributes(false, true, true);

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
        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $bid                   = $attribute->getBid();
            $attributeNumber       = hexdec(sha1($attribute->getFieldType(false) . '_' . $bid, true));
            $attributeColor        = sprintf(
                'rgba(%1$d,%2$d,%3$d,0.9)',
                150 - $attributeNumber / 140,
                100 - $attributeNumber / 100,
                200 - $attributeNumber / 190
            );
            $attributeColors[$bid] = $attributeColor;

            $bid = $attribute->getBid();
            if ($attribute->getFieldType() === GroupType::class) {
                if ($attribute->getUseAtParticipant() || $attribute->getUseAtEmployee()) {
                    /** @var AttributeChoiceOption $choiceOption */
                    foreach ($attribute->getChoiceOptions() as $choiceOption) {

                        $nodes[] = [
                            'id'    => self::choiceOptionNodeId($bid, $choiceOption->getId()),
                            'label' => $choiceOption->getManagementTitle(true),
                            'shape' => 'circle',
                            'color' => $attributeColor,
                        ];
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

                            $edge    = [
                                'from'  => self::participantNodeId($participant->getId()),
                                'to'    => self::choiceOptionNodeId($bid, $groupId),
                                'color' => ['color' => $attributeColors[$bid]],
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
        return 'p.' . $aid;
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
