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
use AppBundle\Form\EventAcquisitionType;
use AppBundle\Form\EventMailType;
use AppBundle\Form\EventType;
use AppBundle\InvalidTokenHttpException;
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
                'eventList'       => array_merge($eventListFuture, $eventListPast)
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
        $eventEntityList = $repository->findAllWithCounts(true, true);

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
                'is_visible'             => (int)$event->isVisible(),
                'is_active'              => (int)$event->isActive(),
                'title'                  => $event->getTitle(),
                'description'            => $event->getTitle(),
                'start_date'             => $eventStartDate,
                'end_date'               => $eventEndDate,
                'participants_confirmed' => $event->getParticipationsConfirmedCount(),
                'participants'           => $event->getParticipationsCount(),
                'status'                 => $eventStatus
            );
        }


        return new JsonResponse($eventList);
    }

    /**
     * Edit page for one single event
     *
     * @Route("/admin/event/{eid}/edit", requirements={"eid": "\d+"}, name="event_edit")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editAction(Request $request)
    {
        $eid = $request->get('eid');

        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

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
            'event/admin/edit.html.twig', array(
                                            'event' => $event,
                                            'form'  => $form->createView(),
                                        )
        );
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/event/{eid}/acquisition", requirements={"eid": "\d+"}, name="event_acquisition_assignment")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editEventAcquisitionAssignmentAction(Request $request)
    {
        $eid        = $request->get('eid');
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $form = $this->createForm(
            EventAcquisitionType::class, $event, array(
                                           'action' => $this->generateUrl(
                                               'event_acquisition_assignment', array('eid' => $eid)
                                           ),
                                       )
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($event);
            $em->flush();
        }

        return $this->redirectToRoute('event', array('eid' => $event->getEid()));
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/event/{eid}", requirements={"eid": "\d+"}, name="event")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function detailEventAction(Request $request)
    {
        $eid        = $request->get('eid');
        $repository = $this->getDoctrine()->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

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
                    'event_acquisition_assignment', array('eid' => $eid)
                ),
            )
        );

        return $this->render(
            'event/admin/detail.html.twig',
            array(
                'event'                     => $event,
                'ageDistribution'           => $ageDistribution,
                'ageDistributionMax'        => $ageDistributionMax,
                'genderDistribution'        => $genderDistribution,
                'participantsCount'         => $participantsCount,
                'form'                      => $form->createView(),
                'acquisitionAssignmentForm' => $acquisitionAssignmentForm->createView()
            )
        );
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/event/{eid}/mail", requirements={"eid": "\d+"}, name="event_mail")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function sendParticipantsEmailAction(Request $request)
    {
        $eid = $request->get('eid');

        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

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

            return $this->redirectToRoute('event', array('eid' => $eid));
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
            'event/admin/new.html.twig', array(
                                           'form' => $form->createView(),
                                       )
        );
    }

    /**
     * Page for list of events
     *
     * @Route("/event/miss/{eid}", requirements={"eid": "\d+"}, name="event_miss")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function eventNotFoundAction($eid)
    {
        return $this->render('event/public/miss.html.twig', array('eid' => $eid));
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
}