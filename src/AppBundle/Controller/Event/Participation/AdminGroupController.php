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
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\GroupFilloutValue;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;

use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\EntityHavingFilloutsInterface;
use AppBundle\Form\GroupFieldAssignEntitiesType;
use AppBundle\Form\GroupType;
use AppBundle\Group\AttributeChoiceOptionUsageDistribution;
use AppBundle\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
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
                if ($field->getUseAtEmployee()) {
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
        $bid          = $attribute->getBid();
        $repository   = $this->getDoctrine()->getRepository(Attribute::class);
        $attribute    = $repository->findWithOptions($bid);
        $choices      = [];
        $distribution = new AttributeChoiceOptionUsageDistribution(
            $this->get('doctrine.orm.entity_manager'), $event, $attribute
        );

        /** @var AttributeChoiceOption $choiceOption */
        foreach ($attribute->getChoiceOptions() as $choiceOption) {
            $choices[] = [
                'bid'                 => $bid,
                'id'                  => $choiceOption->getId(),
                'managementTitle'     => $choiceOption->getManagementTitle(true),
                'formTitle'           => $choiceOption->getFormTitle(),
                'shortTitle'          => $choiceOption->getShortTitle(false),
                'countEmployees'      => $distribution->getOptionDistribution($choiceOption)->getEmployeeCount(),
                'countParticipants'   => $distribution->getOptionDistribution($choiceOption)->getParticipantsCount(),
                'countParticipations' => $distribution->getOptionDistribution($choiceOption)->getParticipationCount(),

            ];

        }

        return new JsonResponse($choices);
    }

    /**
     * Page for list of participants of an event having a provided age at a specific date
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @ParamConverter("choiceOption", class="AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption",
     *                                  options={"id" = "cid"})
     * @Route("/admin/event/{eid}/groups/{bid}/group/{cid}", requirements={"eid": "\d+", "bid": "\d+", "cid": "\d+"},
     *                                          name="admin_event_group_detail")
     * @Security("is_granted('participants_read', event)")
     * @param Event                 $event
     * @param Attribute             $attribute
     * @param AttributeChoiceOption $choiceOption
     * @param Request               $request
     * @return Response
     */
    public function groupDetailsAction(
        Event $event,
        Attribute $attribute,
        AttributeChoiceOption $choiceOption,
        Request $request
    ) {
        $repository = $this->getDoctrine()->getRepository(Attribute::class);
        $usage      = $repository->fetchAttributeChoiceUsage($event, $choiceOption);

        $formOptions        = ['event' => $event, 'choiceOption' => $choiceOption];
        $formParticipants   = $this->createForm(
            GroupFieldAssignEntitiesType::class,
            [],
            array_merge($formOptions, ['entities' => Participant::class])
        );
        $formParticipations = $this->createForm(
            GroupFieldAssignEntitiesType::class,
            [],
            array_merge($formOptions, ['entities' => Participation::class])
        );
        $formEmployees      = $this->createForm(
            GroupFieldAssignEntitiesType::class,
            [],
            array_merge($formOptions, ['entities' => Employee::class])
        );

        $response = $this->handleGroupForms(
            $event,
            $choiceOption,
            [$formParticipants, $formParticipations, $formEmployees], $request
        );
        if ($response) {
            return $response;
        }

        return $this->render(
            'event/admin/group/group-choice-detail.html.twig',
            [
                'event'              => $event,
                'attribute'          => $attribute,
                'choiceOption'       => $choiceOption,
                'usage'              => $usage,
                'formParticipants'   => $formParticipants->createView(),
                'formParticipations' => $formParticipations->createView(),
                'formEmployees'      => $formEmployees->createView(),
            ]
        );
    }

    /**
     * Handle provided {@see GroupFieldAssignEntitiesType} forms and provide redirect response
     *
     * @param Event                 $event        Related event
     * @param AttributeChoiceOption $choiceOption Group
     * @param array                 $forms        Forms to process
     * @param Request               $request      Request to use for processing
     * @return Response|null Response if changes were submitted
     */
    private function handleGroupForms(
        Event $event,
        AttributeChoiceOption $choiceOption,
        array $forms,
        Request $request
    ): ?Response {
        $bid = $choiceOption->getAttribute()->getBid();
        $em  = $this->getDoctrine()->getManager();

        $changed = false;
        foreach ($forms as $form) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entities = $form->get('assign')->getData();
                /** @var EntityHavingFilloutsInterface $entity */
                foreach ($entities as $entity) {
                    /** @var Fillout $fillout */
                    $fillout = $entity->getAcquisitionAttributeFillout($bid, true);
                    $value   = GroupFilloutValue::createForChoiceOption($choiceOption);
                    $fillout->setValue($value->getRawValue());
                    $em->persist($fillout);
                    $changed = true;
                }
            }
        }
        if ($changed) {
            $this->denyAccessUnlessGranted('participants_edit', $event);
            $em->flush();
            return $this->redirectToRoute(
                'admin_event_group_detail',
                [
                    'eid' => $event->getEid(),
                    'bid' => $bid,
                    'cid' => $choiceOption->getId(),
                ]
            );
        }
        return null;
    }

    /**
     * Data for list of @param Event                 $event
     *
     * @param AttributeChoiceOption $choiceOption
     * @return Response
     *@see Employee having specific @see AttributeChoiceOption selected for an @see Event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @ParamConverter("choiceOption", class="AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption",
     *                                  options={"id" = "cid"})
     * @Route("/admin/event/{eid}/groups/{bid}/group/{cid}/employee-data.json", requirements={"eid": "\d+", "bid":
     *                                                                          "\d+", "cid": "\d+"},
     *                                                                          name="admin_event_group_employee_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function groupEmployeeDataAction(Event $event, Attribute $attribute, AttributeChoiceOption $choiceOption)
    {
        $repository = $this->getDoctrine()->getRepository(Attribute::class);
        $usage      = $repository->fetchAttributeChoiceUsage($event, $choiceOption);
        $result     = [];

        /** @var Employee $employee */
        foreach ($usage->getEmployees() as $employee) {
            $row = [
                'gid'       => $employee->getGid(),
                'nameFirst' => $employee->getNameFirst(),
                'nameLast'  => $employee->getNameLast(),
            ];

            /** @var Fillout $fillout */
            foreach ($employee->getAcquisitionAttributeFillouts() as $fillout) {
                if ($fillout->getAttribute()->getUseAtEmployee()) {
                    $row['acq_field_' . $fillout->getAttribute()->getBid()]
                        = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
                }
            }
            $result[] = $row;
        };
        return new JsonResponse($result);
    }

    /**
     * Data for list of @param Event                 $event
     *
     * @param AttributeChoiceOption $choiceOption
     * @return Response
     *@see Participant having specific @see AttributeChoiceOption selected for an @see Event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @ParamConverter("choiceOption", class="AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption",
     *                                  options={"id" = "cid"})
     * @Route("/admin/event/{eid}/groups/{bid}/group/{cid}/participant-data.json", requirements={"eid": "\d+", "bid":
     *                                                                          "\d+", "cid": "\d+"},
     *                                                                          name="admin_event_group_participants_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function groupParticipantDataAction(Event $event, Attribute $attribute, AttributeChoiceOption $choiceOption)
    {
        $repository = $this->getDoctrine()->getRepository(Attribute::class);
        $usage      = $repository->fetchAttributeChoiceUsage($event, $choiceOption);
        $result     = [];

        /** @var Participant $participant */
        foreach ($usage->getParticipants() as $participant) {
            $participation = $participant->getParticipation();
            $row           = [
                'pid'       => $participation->getPid(),
                'aid'       => $participant->getAid(),
                'nameFirst' => $participant->getNameFirst(),
                'nameLast'  => $participant->getNameLast(),
            ];

            /** @var Fillout $fillout */
            foreach ($participation->getAcquisitionAttributeFillouts() as $fillout) {
                if (!$fillout->getAttribute()->isUseForParticipationsOrParticipants()) {
                    continue;
                }
                $row['participation_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
            }

            foreach ($participant->getAcquisitionAttributeFillouts() as $fillout) {
                if (!$fillout->getAttribute()->isUseForParticipationsOrParticipants()) {
                    continue;
                }
                $row['participant_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
            }


            $result[] = $row;
        };
        return new JsonResponse($result);
    }

    /**
     * Data for list of @param Event                 $event
     *
     * @param AttributeChoiceOption $choiceOption
     * @return Response
     *@see Employee having specific @see AttributeChoiceOption selected for an @see Event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @ParamConverter("choiceOption", class="AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption",
     *                                  options={"id" = "cid"})
     * @Route("/admin/event/{eid}/groups/{bid}/group/{cid}/participation-data.json", requirements={"eid": "\d+", "bid":
     *                                                                          "\d+", "cid": "\d+"},
     *                                                                          name="admin_event_group_participation_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function groupParticipationDataAction(Event $event, Attribute $attribute, AttributeChoiceOption $choiceOption)
    {
        $repository = $this->getDoctrine()->getRepository(Attribute::class);
        $usage      = $repository->fetchAttributeChoiceUsage($event, $choiceOption);
        $result     = [];

        /** @var Participation $participation */
        foreach ($usage->getParticipations() as $participation) {
            $row = [
                'pid'       => $participation->getPid(),
                'nameFirst' => $participation->getNameFirst(),
                'nameLast'  => $participation->getNameLast(),
            ];

            /** @var Fillout $fillout */
            foreach ($participation->getAcquisitionAttributeFillouts() as $fillout) {
                if ($fillout->getAttribute()->getUseAtEmployee()) {
                    $row['acq_field_' . $fillout->getAttribute()->getBid()]
                        = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
                }
            }
            $result[] = $row;
        };
        return new JsonResponse($result);
    }

}
