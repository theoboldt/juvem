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
use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\CustomField\GroupCustomFieldValue;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\GroupFieldAssignEntitiesType;
use AppBundle\Form\GroupType;
use AppBundle\Group\AttributeChoiceOptionUsageDistribution;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\JsonResponse;
use AppBundle\Twig\Extension\CustomFieldValue;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;


class AdminGroupController
{
    
    use RenderingControllerTrait, DoctrineAwareControllerTrait, FormAwareControllerTrait, AuthorizationAwareControllerTrait, RoutingControllerTrait;
    
    /**
     * doctrine.orm.entity_manager
     *
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $ormManager;
    
    /**
     * AdminGroupController constructor.
     *
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     * @param FormFactoryInterface $formFactory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     * @param EntityManagerInterface $ormManager
     */
    public function __construct(
        Environment $twig,
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        EntityManagerInterface $ormManager
    )
    {
        $this->twig                 = $twig;
        $this->doctrine             = $doctrine;
        $this->formFactory          = $formFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
        $this->router               = $router;
        $this->ormManager           = $ormManager;
    }
    
    /**
     * List groups of event
     *
     * @CloseSessionEarly
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
     * @CloseSessionEarly
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
                    $choice = htmlentities($choiceOption->getManagementTitle(true));
                    if ($choiceOption->getDeletedAt()) {
                        $choice = '<span style="opacity:0.5;text-decoration:line-through;">'.$choice.'</span>';
                    }

                    $choices[] = $choice;
                }

                if ($field->getUseAtParticipation()) {
                    $acquisition[] = 'Anmeldung';
                }
                if ($field->getUseAtParticipant()) {
                    $acquisition[] = 'Teilnehmer:innen';
                }
                if ($field->getUseAtEmployee()) {
                    $acquisition[] = 'Mitarbeiter:innen';
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
     * @CloseSessionEarly
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
     * @CloseSessionEarly
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
            $this->ormManager, $event, $attribute
        );

        /** @var AttributeChoiceOption $choiceOption */
        foreach ($attribute->getChoiceOptions() as $choiceOption) {
            $usage = $distribution->getOptionDistribution($choiceOption);
            $countEmployees      = $usage->getEmployeeCount();
            $countParticipants   = $usage->getParticipantsCount();
            $countParticipations = $usage->getParticipationCount();
            
            $title = htmlentities($choiceOption->getManagementTitle(true));

            if ($choiceOption->getDeletedAt()) {
                $title = '<span style="opacity:0.5;text-decoration:line-through;">'.$title.'</span>';
                if (!$countEmployees && !$countParticipants && !$countParticipations) {
                    continue;
                }
            }
            $choices[] = [
                'bid'                 => $bid,
                'id'                  => $choiceOption->getId(),
                'managementTitle'     => $title,
                'formTitle'           => $choiceOption->getFormTitle(),
                'shortTitle'          => $choiceOption->getShortTitle(false),
                'countEmployees'      => $countEmployees,
                'countParticipants'   => $countParticipants,
                'countParticipations' => $countParticipations,
            ];

        }

        return new JsonResponse($choices);
    }

    /**
     * Page for list of participants of an event having a provided age at a specific date
     *
     * @CloseSessionEarly
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
        $formParticipants   = $this->formFactory->createNamed(
            'group_field_assign_participants',
            GroupFieldAssignEntitiesType::class,
            [],
            array_merge($formOptions, ['entities' => Participant::class])
        );
        $formParticipations = $this->formFactory->createNamed(
            'group_field_assign_participations',
            GroupFieldAssignEntitiesType::class,
            [],
            array_merge($formOptions, ['entities' => Participation::class])
        );
        $formEmployees      = $this->formFactory->createNamed(
            'group_field_assign_employees',
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
                /** @var EntityHavingCustomFieldValueInterface $entity */
                foreach ($entities as $entity) {
                    $this->denyAccessUnlessGranted('participants_edit', $event);
                    $customFieldValueContainer = $entity->getCustomFieldValues()->getByCustomField($choiceOption->getAttribute());
                    $customFieldValue = $customFieldValueContainer->getValue();
                    if (!$customFieldValue instanceof GroupCustomFieldValue) {
                        throw new \InvalidArgumentException('Unexpected class '.get_class($customFieldValue));
                    }
                    $customFieldValue->setValue($choiceOption->getId());
                    $em->persist($entity);
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
     * @CloseSessionEarly
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

        $customFieldValueExtension = $this->twig->getExtension(CustomFieldValue::class);
        if (!$customFieldValueExtension instanceof CustomFieldValue) {
            throw new \RuntimeException('Need to fetch '.CustomFieldValue::class.' from twig');
        }
        
        /** @var Employee $employee */
        foreach ($usage->getEmployees() as $employee) {
            $row = [
                'gid'       => $employee->getGid(),
                'nameFirst' => $employee->getNameFirst(),
                'nameLast'  => $employee->getNameLast(),
            ];
            
            /** @var CustomFieldValueContainer $customFieldValueContainer */
            foreach ($employee->getCustomFieldValues() as $customFieldValueContainer) {
                $row['custom_field_' . $customFieldValueContainer->getCustomFieldId()]
                    = $customFieldValueExtension->customFieldValue($this->twig, $customFieldValueContainer, $employee, false);
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
     * @CloseSessionEarly
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
        $statusFormatter = ParticipantStatus::formatter();
        $repository      = $this->getDoctrine()->getRepository(Attribute::class);
        $usage           = $repository->fetchAttributeChoiceUsage($event, $choiceOption);
        $result          = [];

        $customFieldValueExtension = $this->twig->getExtension(CustomFieldValue::class);
        if (!$customFieldValueExtension instanceof CustomFieldValue) {
            throw new \RuntimeException('Need to fetch '.CustomFieldValue::class.' from twig');
        }

        /** @var Participant $participant */
        foreach ($usage->getParticipants() as $participant) {
            $participation = $participant->getParticipation();

            $participantStatus = $participant->getStatus(true);

            $age = '';
            if ($participant->hasBirthdayAtEvent()) {
                $age = '<span class="birthday-during-event">';
            }
            if ($participant->getYearsOfLifeAtEvent() !== null) {
                $age .= '<span class="years-of-life">' . $participant->getYearsOfLifeAtEvent() . '</span>';
                $age .= ' <span class="rounded-age">(' . number_format($participant->getAgeAtEvent(), 1, ',', '.') . ')</span>';
                if ($participant->hasBirthdayAtEvent()) {
                    '</span>';
                }
            }
            $participantStatusText = $statusFormatter->formatMask($participantStatus);
            if ($participant->isDeleted()) {
                $participantStatusText .= ' <span class="label label-danger">gelöscht</span>';
            }
            $participantStatusWithdrawn = $participantStatus->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            $participantStatusRejected  = $participantStatus->has(ParticipantStatus::TYPE_STATUS_REJECTED);

            $row           = [
                'pid'       => $participation->getPid(),
                'aid'       => $participant->getAid(),
                'nameFirst' => $participant->getNameFirst(),
                'nameLast'  => $participant->getNameLast(),
                'is_deleted'               => (int)($participant->getDeletedAt() instanceof \DateTime),
                'is_withdrawn'             => (int)$participantStatusWithdrawn,
                'is_rejected'              => (int)$participantStatusRejected,
                'is_withdrawn_or_rejected' => (int)($participantStatusWithdrawn || $participantStatusRejected),
                'is_confirmed'             => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_CONFIRMED),
                'age'                      => $age,
                'status'                   => $participantStatusText,
                'gender'                   => $participant->getGender(),
            ];

            /** @var CustomFieldValueContainer $customFieldValueContainer */
            foreach ($participation->getCustomFieldValues() as $customFieldValueContainer) {
                $row['participation_custom_field_' . $customFieldValueContainer->getCustomFieldId()]
                    = $customFieldValueExtension->customFieldValue($this->twig, $customFieldValueContainer, $participation, false);
            }
            /** @var CustomFieldValueContainer $customFieldValueContainer */
            foreach ($participant->getCustomFieldValues() as $customFieldValueContainer) {
                $row['participant_custom_field_' . $customFieldValueContainer->getCustomFieldId()]
                    = $customFieldValueExtension->customFieldValue($this->twig, $customFieldValueContainer, $participant, false);
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
     * @see Employee having specific @see AttributeChoiceOption selected for an @see Event
     *
     * @CloseSessionEarly
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

        $customFieldValueExtension = $this->twig->getExtension(CustomFieldValue::class);
        if (!$customFieldValueExtension instanceof CustomFieldValue) {
            throw new \RuntimeException('Need to fetch ' . CustomFieldValue::class . ' from twig');
        }

        /** @var Participation $participation */
        foreach ($usage->getParticipations() as $participation) {
            $row = [
                'pid' => $participation->getPid(),
                'nameFirst' => $participation->getNameFirst(),
                'nameLast' => $participation->getNameLast(),
            ];

            /** @var CustomFieldValueContainer $customFieldValueContainer */
            foreach ($participation->getCustomFieldValues() as $customFieldValueContainer) {
                $row['custom_field_' . $customFieldValueContainer->getCustomFieldId()]
                    = $customFieldValueExtension->customFieldValue(
                    $this->twig, $customFieldValueContainer, $participation, false
                );
            }

            $result[] = $row;
        };
        return new JsonResponse($result);
    }

}
