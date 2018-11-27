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
use AppBundle\Entity\Event;

use AppBundle\Form\GroupType;
use AppBundle\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class AdminGroupController extends Controller
{

    /**
     * List groups of event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/groups", requirements={"eid": "\d+"}, name="event_admin_groups")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event Related event
     * @return Response
     */
    public function eventGroupsAction(Event $event)
    {

        return $this->render(
            'event/admin/group/event-group-list.html.twig',
            [
                'event' => $event,
            ]
        );
    }

    /**
     * Data for @see eventGroupsAction()
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/groups-data.json", requirements={"eid": "\d+"}, name="event_admin_groups_data")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event Related event
     * @return JsonResponse
     */
    public function eventGroupsDataAction(Event $event)
    {
        $eventRepository = $this->getDoctrine()->getRepository(Event::class);
        $event           = $eventRepository->findWithAcquisitionAttributes($event->getEid());
        $allFields       = $event->getAcquisitionAttributes(true, true, true, true, true);

        $fields = [];
        /** @var Attribute $field */
        foreach ($allFields as $field) {
            if ($field->getFieldType() === GroupType::class) {
                $choices     = [];
                $acquisition = [];
                /** @var AttributeChoiceOption $choiceOption */
                foreach ($field->getChoiceOptions() as $choiceOption) {
                    $choices[] = $choiceOption->getManagementTitle(true);
                }

                if ($field->getUseAtParticipation()) {
                    $acquisition[] = 'Anmeldung';
                }
                if ($field->getUseAtParticipant()) {
                    $acquisition[] = 'Teilnehmer';
                }
                if ($field->isUseAtEmployee()) {
                    $acquisition[] = 'Mitarbeiter';
                }

                $fields[] = [
                    'bid'             => $field->getBid(),
                    'managementTitle' => $field->getManagementTitle(),
                    'choices'         => implode(', ', $choices),
                    'acquisition'     => implode(', ', $acquisition),
                ];
            }
        }

        return new JsonResponse($fields);
    }

    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @Route("/admin/event/{eid}/groups/{bid}", requirements={"eid": "\d+", "bid": "\d+"},
     *                                          name="admin_event_group_overview")
     * @param Event     $event
     * @param Attribute $attribute
     * @return Response
     */
    public function groupOverviewAction(Event $event, Attribute $attribute)
    {
        return $this->render(
            'event/admin/group/group-choice-list.html.twig',
            [
                'event'     => $event,
                'attribute' => $attribute,
            ]
        );
    }


    /**
     * Data for @see eventGroupsAction()
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @Route("/admin/event/{eid}/groups/{bid}/choices-data.json", requirements={"eid": "\d+", "bid": "\d+"},
     *                                                            name="event_admin_group_overview_data")
     * @Security("is_granted('participants_read', event)")
     * @param Event     $event Related event
     * @param Attribute $attribute
     * @return JsonResponse
     */
    public function groupOverviewDataAction(Event $event, Attribute $attribute)
    {
        $bid             = $attribute->getBid();
        $eventRepository = $this->getDoctrine()->getRepository(Attribute::class);
        $attribute       = $eventRepository->findWithOptions($bid);
        $choices         = [];

        /** @var AttributeChoiceOption $choiceOption */
        foreach ($attribute->getChoiceOptions() as $choiceOption) {
            $choices[] = [
                'bid'             => $bid,
                'id'              => $choiceOption->getId(),
                'managementTitle' => $choiceOption->getManagementTitle(true),
                'formTitle'       => $choiceOption->getFormTitle(),
                'shortTitle'      => $choiceOption->getShortTitle(false),
            ];

        }

        return new JsonResponse($choices);
    }

    /**
     * Page for list of participants of an event having a provided age at a specific date
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @ParamConverter("choiceFillout", class="AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption",
     *                                  options={"id" = "cid"})
     * @Route("/admin/event/{eid}/groups/{bid}/group/{cid}", requirements={"eid": "\d+", "bid": "\d+", "cid": "\d+"},
     *                                          name="admin_event_group_detail")
     * @Security("is_granted('participants_read', event)")
     * @param Event                 $event
     * @param AttributeChoiceOption $choiceFillout
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function groupDetailsAction(Event $event, Attribute $attribute, AttributeChoiceOption $choiceFillout)
    {
        return $this->render(
            'event/admin/group/group-choice-detail.html.twig',
            [
                'event'        => $event,
                'choiceOption' => $choiceFillout,
            ]
        );
    }
}
