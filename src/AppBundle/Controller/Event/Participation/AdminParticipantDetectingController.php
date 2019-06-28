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
     * List groups of event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/detectings", requirements={"eid": "\d+"}, name="event_admin_detectings_list")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event Related event
     * @return Response
     */
    public function participantDetectingFieldsAction(Event $event)
    {
        
        return $this->render(
            'event/admin/participant_detecting/event-detecting-list.html.twig',
            [
                'event' => $event,
            ]
        );
    }
    
    /**
     * Data for {@see participantDetectingFieldsAction()}
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/detectings-data.json", requirements={"eid": "\d+"}, name="event_admin_detectings_data")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event Related event
     * @return JsonResponse
     */
    public function participantDetectingFieldsDataAction(Event $event)
    {
        $eventRepository = $this->getDoctrine()->getRepository(Event::class);
        $event           = $eventRepository->findWithAcquisitionAttributes($event->getEid());
        $allFields       = $event->getAcquisitionAttributes(true, true, true, true, true);
        
        $fields = [];
        /** @var Attribute $field */
        foreach ($allFields as $field) {
            if ($field->getFieldType() === ParticipantDetectingType::class) {
                $acquisition = [];
                
                if ($field->getUseAtParticipation()) {
                    $acquisition[] = 'Anmeldung';
                }
                if ($field->getUseAtParticipant()) {
                    $acquisition[] = 'Teilnehmer';
                }
                if ($field->getUseAtEmployee()) {
                    $acquisition[] = 'Mitarbeiter';
                }
                
                $fields[] = [
                    'bid'             => $field->getBid(),
                    'managementTitle' => $field->getManagementTitle(),
                    'acquisition'     => implode(', ', $acquisition),
                ];
            }
        }
        
        return new JsonResponse($fields);
    }
    
    
    /**
     * Get details for detecting field
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @Route("/admin/event/{eid}/detectings/{bid}", requirements={"eid": "\d+", "bid": "\d+"}, name="event_admin_detectings_overview")
     * @param Event $event
     * @param Attribute $attribute
     * @return Response
     */
    public function participantDetectingOverviewAction(Event $event, Attribute $attribute)
    {
        if ($attribute->getFieldType() !== ParticipantDetectingType::class) {
            throw new \InvalidArgumentException('Requested detecting overview for non-detecting field');
        }
        $bid = $attribute->getBid();
        
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participants            = [];
        /** @var Participant $participant */
        foreach ($participationRepository->participantsList($event, null, true, true) as $participant) {
            $participants[$participant->getId()] = $participant;
        }
        $nodes = [];
        $selections = [];
        
        //$finder = $this->get('app.related_participants_finder');
        /** @var Participant $participant */
        foreach ($participants as $participant) {
            if ($participant->getDeletedAt() || $participant->isRejected() || $participant->isWithdrawn()) {
                continue;
            }
            
            if ($attribute->getUseAtParticipant()) {
                $fillout = $participant->getAcquisitionAttributeFillout($bid, true);
                /** @var ParticipantFilloutValue $value */
                $value       = $fillout->getValue();
                $selectedAid = $value->getSelectedParticipantId();
                $selections[$participant->getId()] = $selectedAid;
                
                $nodes[$participant->getId()] = [
                    'participant' => $participant,
                    'selected'    => $selectedAid !== null ? $participants[$selectedAid] : null,
                    'value'       => $value
                ];
            }
        }
        
        return $this->render(
            'event/admin/participant_detecting/event-detecting-overview.html.twig',
            [
                'event'     => $event,
                'attribute' => $attribute,
                'nodes'     => $nodes,
            ]
        );
    }
    
}