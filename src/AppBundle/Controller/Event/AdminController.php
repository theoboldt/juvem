<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\FilloutTrait;
use AppBundle\Entity\AcquisitionAttribute\Formula\CalculationImpossibleException;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariableValue;
use AppBundle\Entity\AcquisitionAttribute\Variable\NoDefaultValueSpecifiedException;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventAcquisitionAttributeUnavailableException;
use AppBundle\Entity\EventUserAssignment;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationRepository;
use AppBundle\Entity\User;
use AppBundle\Form\AcquisitionAttribute\SpecifyEventSpecificVariableValuesForEventType;
use AppBundle\Form\EventAddUserAssignmentsType;
use AppBundle\Form\EventMailType;
use AppBundle\Form\EventType;
use AppBundle\Form\EventUserAssignmentsType;
use AppBundle\ImageResponse;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Manager\Payment\PaymentManager;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;


class AdminController extends AbstractController
{
    
    /**
     * Page for list of events
     *
     * @Route("/admin/event/list", name="event_list")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function listAction()
    {
        $repository      = $this->getDoctrine()->getRepository(Event::class);
        $eventListFuture = $repository->findEidListFutureEvents();
        $eventListPast   = $repository->findEidListPastEvents();
        
        return $this->render(
            'event/admin/list.html.twig',
            [
                'eventListFuture' => $eventListFuture,
                'eventListPast'   => $eventListPast,
                'eventList'       => array_merge($eventListFuture, $eventListPast),
            ]
        );
    }
    
    /**
     * Data provider for event list grid
     *
     * @Route("/admin/event/list.json", name="event_list_data", methods={"GET", "HEAD"})
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function listDataAction(Request $request)
    {
        $repository      = $this->getDoctrine()->getRepository(Event::class);
        $eventEntityList = $repository->findAllWithCounts(
            true, true, !$this->isGranted('ROLE_ADMIN_EVENT_GLOBAL') ? $this->getUser() : null
        );
        
        $glyphicon = '<span class="glyphicon glyphicon-%s" aria-hidden="true"></span> ';
        
        $eTagData     = '';
        $eTagModified = new \DateTime('2000-01-01');
        
        $eventList = [];
        /** @var Event $event */
        foreach ($eventEntityList as $event) {
            $eventStatus = '';
            
            if ($event->isVisible()) {
                $eventStatus .= sprintf($glyphicon, 'eye-open');
            } else {
                $eventStatus .= sprintf($glyphicon, 'eye-close');
            }
            
            if ($event->isActive()) {
                $eventStatus .= sprintf($glyphicon, 'folder-open');
            } else {
                $eventStatus .= sprintf($glyphicon, 'folder-close');
            }
            
            $eventStartDate = $event->getStartDate()->format(Event::DATE_FORMAT_DATE);
            if ($event->hasEndDate()) {
                $eventEndDate = $event->getEndDate()->format(Event::DATE_FORMAT_DATE);
            } else {
                $eventEndDate = $eventStartDate;
            }
            if ($event->hasStartTime()) {
                $eventStartDate .= ' ' . $event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            }
            if ($event->hasEndTime()) {
                $eventEndDate .= ' ' . $event->getEndTime()->format(Event::DATE_FORMAT_TIME);
            } elseif ($event->hasStartTime()) {
                $eventEndDate .= ' ' . $event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            }
            
            $eventList[] = [
                'eid'                    => $event->getEid(),
                'is_deleted'             => $event->getDeletedAt() ? 1 : 0,
                'is_visible'             => (int)$event->isVisible(),
                'is_active'              => (int)$event->isActive(),
                'title'                  => $event->getTitle(),
                'description'            => $event->getTitle(),
                'start_date'             => $eventStartDate,
                'end_date'               => $eventEndDate,
                'participants_confirmed' => $event->getParticipantsConfirmedCount(),
                'participants'           => $event->getParticipantsCount(),
                'status'                 => $eventStatus,
            ];
            
            $eTagData .= sprintf(
                '-%d.%d.%d.%s-', $event->getEid(), $event->getParticipantsConfirmedCount(),
                $event->getParticipantsCount(), $eventStatus
            );
            if ($event->getModifiedAt() > $eTagModified) {
                $eTagModified = $event->getModifiedAt();
            }
        }
        
        if ($request->isMethod(Request::METHOD_HEAD)) {
            $response = new Response();
        } else {
            $response = new JsonResponse($eventList);
        }
        
        $eTag = sha1($eTagData . $eTagModified->format('r'));
        $response->setEtag($eTag)
                 ->setLastModified($eTagModified);
        
        return $response;
    }
    
    /**
     * Edit page for one single event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/edit", requirements={"eid": "\d+"}, name="event_edit")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function editAction(Request $request, Event $event)
    {
        $this->denyAccessUnlessGranted('edit', $event);
        $form = $this->createForm(EventType::class, $event);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            
            $em->persist($event);
            $em->flush();
            
            return $this->redirectToRoute('event', ['eid' => $event->getEid()]);
        }
        
        return $this->render(
            'event/admin/edit.html.twig',
            [
                'event'           => $event,
                'form'            => $form->createView(),
                'pageDescription' => $event->getDescriptionMeta(true),
            ]
        );
    }
    
    /**
     * Detail page for one single event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid", "include" = "participants"})
     * @Route("/admin/event/{eid}", requirements={"eid": "\d+"}, name="event")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function detailEventAction(Request $request, Event $event)
    {
        $this->denyAccessUnlessGranted('read', $event);
        $repository         = $this->getDoctrine()->getRepository(Event::class);
        $ageDistribution    = $repository->participantsAgeDistribution($event);
        $ageDistributionMax = count($ageDistribution) ? max($ageDistribution) : 0;
        $genderDistribution = $repository->participantsGenderDistribution($event);
        $participantsCount  = $repository->participantsCount($event);
        $employeeCount      = $repository->employeeCount($event);
        
        $form = $this->createFormBuilder()
                     ->add('action', HiddenType::class)
                     ->getForm();
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->get('action')->getData();
            
            switch ($action) {
                case 'delete':
                    $event->setDeletedAt(new \DateTime());
                    $this->addFlash(
                        'success',
                        'Die Veranstaltung wurde in den Papierkorb verschoben'
                    );
                    break;
                case 'restore':
                    $event->setDeletedAt(null);
                    $this->addFlash(
                        'success',
                        'Die Veranstaltung wurde wiederhergestellt'
                    );
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            return $this->redirectToRoute('event', ['eid' => $event->getEid()]);
        }
        
        $groupCount       = 0;
        $detectingsCount  = 0;
        $numberFieldCount = 0;
        /** @var Attribute $attribute */
        foreach ($event->getAcquisitionAttributes() as $attribute) {
            switch ($attribute->getFieldType()) {
                case \AppBundle\Form\GroupType::class:
                    ++$groupCount;
                    break;
                case \AppBundle\Form\ParticipantDetectingType::class:
                    ++$detectingsCount;
                    break;
                case \Symfony\Component\Form\Extension\Core\Type\NumberType::class:
                    if (!$attribute->isPublic()) {
                        ++$numberFieldCount;
                    }
                    break;
            }
        }
        
        return $this->render(
            'event/admin/detail.html.twig',
            [
                'event'              => $event,
                'groupCount'         => $groupCount,
                'detectingsCount'    => $detectingsCount,
                'numberFieldCount'   => $numberFieldCount,
                'pageDescription'    => $event->getDescriptionMeta(true),
                'ageDistribution'    => $ageDistribution,
                'ageDistributionMax' => $ageDistributionMax,
                'genderDistribution' => $genderDistribution,
                'participantsCount'  => $participantsCount,
                'employeeCount'      => $employeeCount,
                'form'               => $form->createView(),
            ]
        );
    }
    
    /**
     * Detail page for one single event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid", "include" = "participants"})
     * @Route("/admin/event/{eid}/location", requirements={"eid": "\d+"}, name="event_participants_location")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @return Response
     */
    public function locationEventAction(Event $event): Response
    {
        return $this->render(
            'event/admin/participant-location-distribution.html.twig',
            [
                'event' => $event,
            ]
        );
    }
    
    
    /**
     * Detail page for one single event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/payment_summary.json", requirements={"eid": "\d+"}, name="event_payment_summary")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function eventPaymentSummaryAction(Request $request, Event $event): Response
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participants            = $participationRepository->participantsList($event, null, false, false);
        $paymentManager          = $this->get('app.payment_manager');
        
        $expectedVolume   = 0;
        $additionalVolume = 0;
        $missingVolume    = 0;
        
        try {
            /** @var Participant $participant */
            foreach ($participants as $participant) {
                if (!$participant->isConfirmed()) {
                    continue;
                }
                $price = $paymentManager->getPriceForParticipant($participant, false);
                if ($price !== null) {
                    $expectedVolume += $price;
                }
                $toPay = $paymentManager->getToPayValueForParticipant($participant, false);
                if ($toPay !== null) {
                    if ($toPay < 0) {
                        $additionalVolume += -1 * $toPay;
                    } else {
                        $missingVolume += $toPay;
                    }
                }
            }
        } catch (CalculationImpossibleException $e) {
            if ($e->getPrevious() instanceof NoDefaultValueSpecifiedException) {
                $message
                    = 'Preis kann nicht berechnet werden, da in Formeln veranstaltungsspezifische Variablen verwendet werden, für die kein Wert für diese Veranstaltung festgelgt ist und für die kein Standard-Wert konfiguriert ist. Die Variablen-Werte sollten überprüft werden.';
            } else {
                $message = 'Preis kann nicht berechnet werden';
            }
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => ['severity' => 'danger', 'content' => $message],
                ]
            );
        }
        
        $totalVolume = $expectedVolume + $additionalVolume;
        if (round($totalVolume, 4) == 0.0) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => ['severity' => 'info', 'content' => 'Keine Zahlungen erwartet.'],
                ]
            );
        }
        $barPaidShare       = ($totalVolume - ($missingVolume + $additionalVolume)) / $totalVolume;
        $barMissingShare    = $missingVolume / $totalVolume;
        $barAdditionalShare = $additionalVolume / $totalVolume;
        
        return new JsonResponse(
            [
                'success'           => true,
                'bars'              => [
                    'paid'       => round($barPaidShare * 100, 2),
                    'missing'    => round($barMissingShare * 100, 2),
                    'additional' => round($barAdditionalShare * 100, 2),
                ],
                'paid_volume'       => [
                    'cents' => ($totalVolume - $missingVolume - $additionalVolume),
                    'euros' => number_format(($totalVolume - $missingVolume - $additionalVolume) / 100, 2, ',', "'"),
                ],
                'paid_total_volume' => [
                    'cents' => ($totalVolume - $missingVolume),
                    'euros' => number_format(($totalVolume - $missingVolume) / 100, 2, ',', "'"),
                ],
                'expected_volume'   => [
                    'cents' => $expectedVolume,
                    'euros' => number_format($expectedVolume / 100, 2, ',', "'"),
                ],
                'additional_volume' => [
                    'cents' => $additionalVolume,
                    'euros' => number_format($additionalVolume / 100, 2, ',', "'"),
                ],
                'missing_volume'    => [
                    'cents' => $missingVolume,
                    'euros' => number_format($missingVolume / 100, 2, ',', "'"),
                ],
            ]
        );
    }
    
    /**
     * Show variables for event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/variable", requirements={"eid": "\d+"}, name="admin_event_variable", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN_EVENT', event)")
     * @return Response
     */
    public function showEventVariablesAction(Event $event): Response
    {
        $variableRepository = $this->getDoctrine()->getRepository(EventSpecificVariable::class);
        
        $priceManager     = $this->get('app.price_manager');
        $attributes       = $priceManager->attributesWithFormula();
        $variableResolver = $priceManager->resolver();
        
        $variableUse    = [];
        $usedAttributes = [];
        foreach ($attributes as $attribute) {
            try {
                $eventHasAttribute = (bool)$event->getAcquisitionAttribute($attribute->getBid());
            } catch (EventAcquisitionAttributeUnavailableException $e) {
                $eventHasAttribute = false;
            }
            if ($eventHasAttribute) {
                $usedAttributes[$attribute->getBid()] = $attribute;
            }
            
            foreach ($variableResolver->getUsedVariables($attribute) as $attributeVariable) {
                if ($attributeVariable instanceof EventSpecificVariable) {
                    $variableUse[$attributeVariable->getId()][] = $attribute;
                }
            }
        }
        
        $variableEntities = $variableRepository->findAllNotDeleted();
        $variables        = [];
        
        $values = $variableRepository->findAllValuesForEvent($event);
        /** @var EventSpecificVariable $variable */
        foreach ($variableEntities as $variable) {
            $variableValue      = $values[$variable->getId()] ?? null;
            $variableAttributes = $variableUse[$variable->getId()] ?? [];
            
            if ($variableValue === null && !$variable->hasDefaultValue()) {
                foreach ($variableAttributes as $attribute) {
                    $this->addFlash(
                        'warning',
                        sprintf(
                            'Die Variable <code>%s</code> (<i>%s</i>) wird in der Formel des Feldes <a href="%s">%s</a> verwendet. Obwohl für diese Variable kein Standardwert konfiguriert wurde, ist für diese Veranstaltungen kein Werte eingestellt. Sie sollten die umgehend die <a href="%s">Werte für Variablen konfigurieren</a>.',
                            $variable->getFormulaVariable(),
                            $variable->getDescription(),
                            $this->generateUrl(
                                'acquisition_detail', ['bid' => $attribute->getBid()]
                            ),
                            $attribute->getManagementTitle(),
                            $this->generateUrl(
                                'admin_event_variable_configure', ['eid' => $event->getEid()]
                            )
                        )
                    );
                }
            }
            
            $variables[] = [
                'variable'   => $variable,
                'value'      => $variableValue,
                'attributes' => $variableAttributes,
            ];
        }
        
        return $this->render(
            'event/admin/variable-detail.html.twig',
            [
                'event'     => $event,
                'variables' => $variables,
            ]
        );
    }
    
    /**
     * Configure all variables for a single event
     *
     * @Route("/admin/event/{eid}/variable/configure", requirements={"eid": "\d+"},
     *                                                 name="admin_event_variable_configure")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @param Event $event
     * @return Response
     */
    public function configureEventVariablesAction(Request $request, Event $event): Response
    {
        $variableRepository = $this->getDoctrine()->getRepository(EventSpecificVariable::class);
        
        $form = $this->createForm(
            SpecifyEventSpecificVariableValuesForEventType::class,
            null,
            [
                SpecifyEventSpecificVariableValuesForEventType::FIELD_EVENT => $event,
            ]
        );
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            
            /** @var Form $formElement */
            foreach ($form as $formElement) {
                /** @var EventSpecificVariableValue $variableValue */
                $variableValue = $formElement->getData();
                
                if ($variableValue->getValue() !== null) {
                    $em->persist($variableValue);
                } else {
                    $value = $variableRepository->findForVariableAndEvent($event, $variableValue->getVariable(), false);
                    if ($value) {
                        $em->remove($variableValue);
                    }
                }
            }
            $em->flush();
            $this->addFlash(
                'success',
                'Die Werte für die Variablen wurden gespeichert'
            );
            return $this->redirectToRoute(
                'admin_event_variable', ['eid' => $event->getEid()]
            );
        }
        
        return $this->render(
            'event/admin/variable-configure.html.twig',
            [
                'event' => $event,
                'form'  => $form->createView(),
            ]
        );
    }
    
    
    /**
     * Detail page for one single event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid", "include" = "participations"})
     * @Route("/admin/event/{eid}/mail", requirements={"eid": "\d+"}, name="event_mail")
     * @Security("is_granted('participants_edit', event)")
     */
    public function sendParticipantsEmailAction(Request $request, Event $event)
    {
        $form = $this->createForm(EventMailType::class);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data      = $form->getData();
            $recipient = $data['recipient'];
            unset($data['recipient']);
            
            $participationManager = $this->get('app.participation_manager');
            $participationManager->mailEventParticipants($data, $event, $recipient);
            $this->addFlash(
                'info',
                'Die Benachrichtigungs-Emails wurden versandt'
            );
            
            return $this->redirectToRoute('event', ['eid' => $event->getEid()]);
        }
        
        return $this->render(
            'event/admin/mail.html.twig',
            [
                'event' => $event,
                'form'  => $form->createView(),
            ]
        );
    }
    
    /**
     * Detail page for one single event
     *
     * @Route("/admin/mail/template", name="mail_template")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function emailTemplateAction()
    {
        return $this->render('mail/notify-participants.html.twig');
    }
    
    /**
     * Create a new event
     *
     * @Route("/admin/event/new", name="event_new")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function newAction(Request $request)
    {
        $event = new Event();
        $event->setStartDate(new \DateTime('today'));
        $event->setEndDate(new \DateTime('tomorrow'));
        
        $form = $this->createForm(EventType::class, $event);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            
            $em->persist($event);
            if ($this->getUser()) {
                /** @var User $user */
                $user = $this->getUser();
                if (!$user->hasRole(\AppBundle\Entity\User::ROLE_ADMIN_EVENT_GLOBAL)) {
                    $assignment = new EventUserAssignment($event, $user);
                    $assignment->setAllowedToEdit(true);
                    $assignment->setAllowedToManageParticipants(true);
                    $assignment->setAllowedToComment(true);
                    $assignment->setAllowedToReadComments(true);
                    $event->getUserAssignments()->add($assignment);
                    $em->persist($assignment);
                }
            }
            $em->flush();
            
            return $this->redirectToRoute('event', ['eid' => $event->getEid()]);
        }
        
        return $this->render(
            'event/admin/new.html.twig',
            ['form' => $form->createView()]
        );
    }
    
    /**
     * Handler for subscription button
     *
     * @Route("/admin/event/subscription", name="event_admin_subscription")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function subscriptionAction(Request $request)
    {
        $token    = $request->get('_token');
        $eid      = $request->get('eid');
        $valueNew = $request->get('valueNew');
        
        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('Eventsubscribe' . $eid)) {
            throw new InvalidTokenHttpException();
        }
        $repository = $this->getDoctrine()->getRepository(Event::class);
        $event      = $repository->findOneBy(['eid' => $eid]);
        $this->denyAccessUnlessGranted('read', $event);
        if (!$event) {
            throw new NotFoundHttpException('Could not find requested event');
        }
        
        if ($valueNew) {
            $event->addSubscriber($this->getUser());
        } else {
            $event->removeSubscriber($this->getUser());
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($event);
        $em->flush();
        
        return new Response('', Response::HTTP_NO_CONTENT);
    }
    
    /**
     * Access uploaded image
     *
     * @Route("/uploads/event/{filename}", requirements={"filename": "([^/])+"}, name="event_upload_image")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function uploadEventImageAction(Request $request, string $filename)
    {
        $uploadManager = $this->get('app.upload_image_manager');
        $image         = $uploadManager->fetch($filename);
        
        if (!$image->exists()) {
            throw new NotFoundHttpException('Requested image not found');
        }
        
        return ImageResponse::createFromRequest($image, $request);
    }
    
    /**
     * Manage User assignments of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid", "include" = "users"})
     * @Route("/admin/event/{eid}/users", requirements={"eid": "\d+"}, name="event_user_admin")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @param Event $event
     * @return Response
     */
    public function manageUserAssignmentsAction(Request $request, Event $event)
    {
        $this->denyAccessUnlessGranted('edit', $event);
        $originalAssignments = new ArrayCollection();
        $em                  = $this->getDoctrine()->getManager();
        
        $formAddUsers = $this->createForm(EventAddUserAssignmentsType::class, null, ['event' => $event]);
        $formAddUsers->handleRequest($request);
        if ($formAddUsers->isSubmitted() && $formAddUsers->isValid()) {
            $assignUser = $formAddUsers->get('assignUser');
            /** @var User $user */
            foreach ($assignUser->getData() as $user) {
                $assignment = new EventUserAssignment($event, $user);
                $event->getUserAssignments()->add($assignment);
            }
            $em->persist($event);
            $em->flush();
            $this->addFlash(
                'success',
                'Weitere Benutzer hinzugefügt'
            );
        }
        foreach ($event->getUserAssignments() as $assignment) {
            $originalAssignments->add($assignment);
        }
        
        $form = $this->createForm(EventUserAssignmentsType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($originalAssignments as $assignment) {
                if (false === $event->getUserAssignments()->contains($assignment)) {
                    $em->remove($assignment);
                }
            }
            $em->persist($event);
            $em->flush();
            $this->addFlash(
                'success',
                'Änderungen an den Zuweisungen gespeichert'
            );
        }
        
        return $this->render(
            'event/admin/user-assignment.html.twig',
            [
                'event'       => $event,
                'form'        => $form->createView(),
                'formAddUser' => $formAddUsers->createView(),
            ]
        );
    }
    
    /**
     * Update specific age and date of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid", "include" = "users"})
     * @Route("/admin/event/{eid}/update-specific-age", requirements={"eid": "\d+"},
     *                                                  name="event_admin_update_specific_age")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @param Event $event
     * @return JsonResponse
     */
    public function updateSpecificAgeAction(Request $request, Event $event)
    {
        $token = $request->get('_token');
        
        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('filter-specific-age-' . $event->getEid())) {
            throw new InvalidTokenHttpException();
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $specificAge  = (int)$request->get('specificAge');
        $specificDate = new \DateTime($request->get('specificDate'));
        $specificDate->setTime(10, 0, 0);
        
        $event->setSpecificAge($specificAge);
        $event->setSpecificDate($specificDate);
        $em->persist($event);
        $em->flush();
        
        return new JsonResponse(['']);
    }
    
    /**
     * Generate order
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/order", requirements={"eid": "\d+"}, name="event_admin_order")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     * @param Event $event Related event
     * @param Request $request
     * @return Response
     */
    public function createOrderAction(Event $event, Request $request)
    {
        $formBuilder = $this->createFormBuilder();
        
        $choices = [];
        
        
        /** @var Attribute $attribute */
        foreach ($event->getAcquisitionAttributes() as $attribute) {
            if ($attribute->getFieldType() === \Symfony\Component\Form\Extension\Core\Type\NumberType::class
                && !$attribute->isPublic()
            ) {
                $choices[$attribute->getManagementTitle()] = $attribute->getBid();
            }
        }
        $formBuilder->add(
            'attribute', ChoiceType::class, ['label' => 'Feld', 'choices' => $choices]
        );
        $formBuilder->add(
            'reset', CheckboxType::class,
            ['label' => 'Vorhandene Reihenfolge zurücksetzen und überschreiben', 'required' => false]
        );
        
        $form = $formBuilder->getForm();
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $attributeBid = $form->get('attribute')->getData();
            $reset        = $form->get('reset')->getData();
            
            $attribute = $event->getAcquisitionAttribute($attributeBid);
            /** @var ParticipationRepository $participationRepository */
            $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
            
            if ($attribute->getUseAtParticipant()) {
                $elements = $participationRepository->participantsList($event, null, true, true);
                $this->updateFilloutOrder($elements, $attributeBid, $reset);
                $this->addFlash(
                    'success',
                    'Reihenfolge für Teilnehmer für das Feld <i>' . htmlspecialchars($attribute->getManagementTitle()) .
                    '</i> festgelegt'
                );
            }
            if ($attribute->getUseAtParticipation()) {
                $elements = $participationRepository->participationsList($event, false, true, null);
                $this->updateFilloutOrder($elements, $attributeBid, $reset);
                $this->addFlash(
                    'success',
                    'Reihenfolge für Anmeldungen für das Feld <i>' .
                    htmlspecialchars($attribute->getManagementTitle()) . '</i> festgelegt'
                );
            }
            
            if ($attribute->getUseAtEmployee()) {
                $this->addFlash(
                    'warning',
                    'Reihenfolgen für Betreuer können noch nicht festgelegt werden'
                );
            }
            
            return $this->redirectToRoute('event_admin_order', ['eid' => $event->getEid()]);
        }
        
        return $this->render(
            'event/admin/create-order.html.twig',
            [
                'event' => $event,
                'form'  => $form->createView(),
            ]
        );
    }
    
    /**
     * Update fillout order
     *
     * @param array $elements
     * @param int $attributeBid
     * @param bool $reset
     * @return int
     */
    private function updateFilloutOrder(array $elements, int $attributeBid, bool $reset): int
    {
        $valueMax = 0;
        /** @var FilloutTrait $element */
        foreach ($elements as $element) {
            $fillout = $element->getAcquisitionAttributeFillout($attributeBid, true);
            $value   = $fillout->getValue()->getTextualValue();
            
            if ($reset) {
                $fillout->setValue('');
            } elseif (!empty($value) && $value > $valueMax) {
                $valueMax = $value;
            }
        }
        
        $em = $this->getDoctrine()->getManager();

        shuffle($elements);
        $index = ($valueMax + 1);
        foreach ($elements as $element) {
            $fillout = $element->getAcquisitionAttributeFillout($attributeBid, true);
            if (empty($fillout->getValue()->getTextualValue())) {
                $fillout->setValue($index++);
                $em->persist($fillout);
            }
        }
        
        $em->flush();
        
        return $index - 1;
    }
    
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        $services                        = parent::getSubscribedServices();
        $services['app.payment_manager'] = PaymentManager::class;
        return $services;
    }
    
}
