<?php
namespace AppBundle\Controller\Event;


use AppBundle\Entity\Event;
use AppBundle\Form\EventAcquisitionType;
use AppBundle\Form\EventMailType;
use AppBundle\Form\EventType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


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
        $repository      = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');
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
        $repository      = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');
        $eventEntityList = $repository->findAll();

        $dateFormatDay     = 'd.m.y';
        $dateFormatDayHour = 'd.m.y H:i';
        $glyphicon         = '<span class="glyphicon glyphicon-%s" aria-hidden="true"></span> ';

        $eventList = array();
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

            $eventStartFormat = $dateFormatDayHour;
            if ($event->getStartDate()
                      ->format('Hi') == '0000'
            ) {
                $eventStartFormat = $dateFormatDay;
            }
            $eventEndFormat = $dateFormatDayHour;
            if ($event->getEndDate()
                      ->format('Hi') == '0000'
            ) {
                $eventEndFormat = $dateFormatDay;
            }

            $eventList[] = array(
                'eid'         => $event->getEid(),
                'is_visible'  => (int)$event->isVisible(),
                'is_active'   => (int)$event->isActive(),
                'title'       => $event->getTitle(),
                'description' => $event->getTitle(),
                'start_date'  => $event->getStartDate()
                                       ->format($eventStartFormat),
                'end_date'    => $event->getEndDate()
                                       ->format($eventEndFormat),
                'status'      => $eventStatus
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

        if ($form->isValid()) {
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
        if ($form->isValid()) {
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
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $ageDistribution    = $repository->participantsAgeDistribution($event);
        $genderDistribution = $repository->participantsGenderDistribution($event);
        $participantsCount  = $repository->participantsCount($event);

        $form = $this->createFormBuilder()
                     ->add('action', HiddenType::class)
                     ->getForm();

        $acquisitionAssignmentForm = $this->createForm(
            EventAcquisitionType::class, $event, array(
                                           'action' => $this->generateUrl(
                                               'event_acquisition_assignment', array('eid' => $eid)
                                           ),
                                       )
        );

        $form->handleRequest($request);
        if ($form->isValid()) {
            $action = $form->get('action')
                           ->getData();
            switch ($action) {
                case 'delete':
                    $event->setDeletedAt(new \DateTime());
                    break;
                case 'restore':
                    $event->setDeletedAt(null);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
        }

        return $this->render(
            'event/admin/detail.html.twig', array(
                                              'event'                     => $event,
                                              'ageDistribution'           => $ageDistribution,
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

        if ($form->isValid()) {
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

        if ($form->isValid()) {
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
}