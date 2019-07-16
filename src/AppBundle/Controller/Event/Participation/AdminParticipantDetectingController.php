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
use AppBundle\Entity\AcquisitionAttribute\ParticipantFilloutValue;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
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
     * @param Event $event
     * @param Attribute $attribute
     * @return Response
     */
    public function participantDetectingOverviewAction(Event $event)
    {
        $attributes = [];
        foreach ($event->getAcquisitionAttributes(false, true, true) as $attribute) {
            if ($attribute->getFieldType() === ParticipantDetectingType::class) {
                $attributes[] = $attribute;
            }
        }
        
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
        foreach ($attributes as $attribute) {
            $bid                   = $attribute->getBid();
            $hexNumber             = hexdec(sha1($bid, true));
            $attributeColors[$bid] = sprintf(
                'rgba(%1$d,%2$d,%3$d,0.9)',
                150 - $hexNumber / 140,
                100 - $hexNumber / 100,
                200 - $hexNumber / 190,
                );
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
                'id'          => (int)$participant->getId(),
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
                    /** @var ParticipantFilloutValue $value */
                    $value       = $fillout->getValue();
                    $selectedAid = $value->getSelectedParticipantId();
                    
                    if ($selectedAid) {
                        $edge = [
                            'from'   => (int)$participant->getId(),
                            'to'     => (int)$selectedAid,
                            'arrows' => 'to',
                            'color'  => ['color' => $attributeColors[$bid]],
                        ];
                        if ($value->isSystemSelection()) {
                            $edge['dashes'] = true;
                        }
                        
                        $edges[] = $edge;
                    }
                    
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
}
