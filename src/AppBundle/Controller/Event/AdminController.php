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


use AppBundle\Entity\Event;
use AppBundle\Entity\EventUserAssignment;
use AppBundle\Entity\User;
use AppBundle\Form\EventAcquisitionType;
use AppBundle\Form\EventAddUserAssignmentsType;
use AppBundle\Form\EventMailType;
use AppBundle\Form\EventType;
use AppBundle\Form\EventUserAssignmentsType;
use AppBundle\ImageResponse;
use AppBundle\InvalidTokenHttpException;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class AdminController extends Controller
{

    /**
     * Page for list of events
     *
     * @Route("/admin/event/list", name="event_list")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listAction()
    {
        $repository      = $this->getDoctrine()->getRepository('AppBundle:Event');
        $eventListFuture = $repository->findEidListFutureEvents();
        $eventListPast   = $repository->findEidListPastEvents();

        return $this->render(
            'event/admin/list.html.twig',
            array(
                'eventListFuture' => $eventListFuture,
                'eventListPast'   => $eventListPast,
                'eventList'       => array_merge($eventListFuture, $eventListPast),
            )
        );
    }

    /**
     * Data provider for event list grid
     *
     * @Route("/admin/event/list.json", name="event_list_data")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listDataAction(Request $request)
    {
        $repository      = $this->getDoctrine()->getRepository('AppBundle:Event');
        $eventEntityList = $repository->findAllWithCounts(
            true, true, !$this->isGranted('ROLE_ADMIN_EVENT_GLOBAL') ? $this->getUser() : null
        );

        $glyphicon = '<span class="glyphicon glyphicon-%s" aria-hidden="true"></span> ';

        $eventList = array();
        /** @var Event $event */
        foreach ($eventEntityList as $event) {
            $eventStatus    = '';
            $eventStartDate = '';
            $eventEndDate   = '';

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

            $eventList[] = array(
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
            );
        }


        return new JsonResponse($eventList);
    }

    /**
     * Edit page for one single event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/edit", requirements={"eid": "\d+"}, name="event_edit")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
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

            return $this->redirectToRoute('event', array('eid' => $event->getEid()));
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
     * Edit acquisitions
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/acquisition", requirements={"eid": "\d+"}, name="event_acquisition_assignment")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editEventAcquisitionAssignmentAction(Request $request, Event $event)
    {
        $this->denyAccessUnlessGranted('edit', $event);
        $form = $this->createForm(
            EventAcquisitionType::class,
            $event,
            [
                'action' => $this->generateUrl('event_acquisition_assignment', ['eid' => $event->getEid()]),
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
        }

        return $this->redirectToRoute('event', array('eid' => $event->getEid()));
    }

    /**
     * Detail page for one single event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid", "include" = "participants"})
     * @Route("/admin/event/{eid}", requirements={"eid": "\d+"}, name="event")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function detailEventAction(Request $request, Event $event)
    {
        $this->denyAccessUnlessGranted('read', $event);
        $repository         = $this->getDoctrine()->getRepository('AppBundle:Event');
        $ageDistribution    = $repository->participantsAgeDistribution($event);
        $ageDistributionMax = count($ageDistribution) ? max($ageDistribution) : 0;
        $genderDistribution = $repository->participantsGenderDistribution($event);
        $participantsCount  = $repository->participantsCount($event);

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
        }

        $acquisitionAssignmentForm = $this->createForm(
            EventAcquisitionType::class,
            $event,
            array(
                'action' => $this->generateUrl(
                    'event_acquisition_assignment', array('eid' => $event->getEid())
                ),
            )
        );

        return $this->render(
            'event/admin/detail.html.twig',
            [
                'event'                     => $event,
                'pageDescription'           => $event->getDescriptionMeta(true),
                'ageDistribution'           => $ageDistribution,
                'ageDistributionMax'        => $ageDistributionMax,
                'genderDistribution'        => $genderDistribution,
                'participantsCount'         => $participantsCount,
                'form'                      => $form->createView(),
                'acquisitionAssignmentForm' => $acquisitionAssignmentForm->createView()
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
            $data = $form->getData();

            $participationManager = $this->get('app.participation_manager');
            $participationManager->mailEventParticipants($data, $event);
            $this->addFlash(
                'info',
                'Die Benachrichtigungs-Emails wurden versandt'
            );

            return $this->redirectToRoute('event', array('eid' => $event->getEid()));
        }

        return $this->render(
            'event/admin/mail.html.twig', array(
                                            'event' => $event,
                                            'form'  => $form->createView(),
                                        )
        );
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/mail/template", name="mail_template")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function emailTemplateAction()
    {
        return $this->render('mail/notify-participants.html.twig');
    }

    /**
     * Create a new event
     *
     * @Route("/admin/event/new", name="event_new")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
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
            $em->flush();

            return $this->redirectToRoute('event_list');
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
     * @Security("has_role('ROLE_ADMIN_EVENT')")
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
        $repository = $this->getDoctrine()->getRepository('AppBundle:Event');
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
     * @Security("has_role('ROLE_ADMIN_EVENT')")
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
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @param Event   $event
     * @return Response
     */
    public function manageUserAssignmentsAction(Request $request, Event $event)
    {
        $this->denyAccessUnlessGranted('edit', $event);
        $originalAssignments = new ArrayCollection();
        $em = $this->getDoctrine()->getManager();

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
}