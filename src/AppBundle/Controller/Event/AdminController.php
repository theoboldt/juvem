<?php
namespace AppBundle\Controller\Event;


use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class AdminController extends Controller
{

    /**
     * Page for list of events
     *
     * @Route("/admin/event/list", name="event_list")
     */
    public function listAction()
    {
        return $this->render('event/list.html.twig');
    }

    /**
     * Data provider for event list grid
     *
     * @Route("/admin/event/list.json", name="event_list_data")
     */
    public function listDataAction(Request $request)
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');
        /*
                $criteria   = array();
                $eventEntityList    = $repository->findBy(
                    $criteria, array('title' => $request->get('order')), $request->get('limit'), $request->get('offset')
                );
        // using client side paginations and filtering
        */
        $eventEntityList = $repository->findAll();

        $dateFormatDay = 'd.m.y';
        $dateFormatDayHour = 'd.m.y H:i';
        $glyphicon = '<span class="glyphicon glyphicon-%s" aria-hidden="true"></span> ';

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
            if ($event->getStartDate()->format('Hi') == '0000') {
                $eventStartFormat = $dateFormatDay;
            }
            $eventEndFormat = $dateFormatDayHour;
            if ($event->getEndDate()->format('Hi') == '0000') {
                $eventEndFormat = $dateFormatDay;
            }

            $eventList[] = array(
                'eid'         => $event->getEid(),
                'title'       => $event->getTitle(),
                'description' => $event->getTitle(),
                'start_date'  => $event->getStartDate()->format($eventStartFormat),
                'end_date'    => $event->getEndDate()->format($eventEndFormat),
                'status'      => $eventStatus
            );
        }


        return new JsonResponse($eventList);
    }

    /**
     * Edit page for one single event
     *
     * @Route("/admin/event/{eid}/edit", requirements={"eid": "\d+"}, name="event_edit")
     */
    public function editAction(Request $request)
    {
        $eid    = $request->get('eid');

        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirectToRoute('event_miss', array('eid' => $eid));
        }

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($event);
            $em->flush();

            return $this->redirect('/event/' . $event->getEid());
        }

        return $this->render('event/edit.html.twig', array(
            'event' => $event,
            'form' => $form->createView(),
        ));
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/event/{eid}", requirements={"eid": "\d+"}, name="event")
     */
    public function detailEventAction($eid)
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirect('event_miss');
        }

        $eventDeleteModal = array(
            'id'      => 'evenDeleteModal',
            'title'   => 'Ereignis löschen',
            'message' => 'Soll dieses Ereignis wirklich gelöscht werden?',
            'action'  => 'Löschen',
            'form'    => $this->createForm(ModalActionType::class, array('id' => $eid, 'area' => 'event'))->createView()
        );

        $buttonState = array(
            'entityName'   => 'Event',
            'propertyName' => 'isActive',
            'entityId'     => $eid,
            'isEnabled'    => $event->isActive(),
            'buttons'      => array(
                'buttonEnable'  => array(
                    'label' => 'Aktivieren',
                    'glyph' => 'folder-open'
                ),
                'buttonDisable' => array(
                    'label' => 'Deaktivieren',
                    'glyph' => 'folder-close'
                )
            )
        );
        $buttonVisibility = array(
            'entityName'   => 'Event',
            'propertyName' => 'isVisible',
            'entityId'     => $eid,
            'isEnabled'    => $event->isVisible(),
            'buttons'      => array(
                'buttonEnable'  => array(
                    'label' => 'Sichtbar schalten',
                    'glyph' => 'eye-open'
                ),
                'buttonDisable' => array(
                    'label' => 'Verstecken',
                    'glyph' => 'eye-close'
                )
            )
        );

        return $this->render('event/detail.html.twig', array(
            'event'            => $event,
            'eventDeleteModal' => $eventDeleteModal,
            'buttonState'      => $buttonState,
            'buttonVisibility' => $buttonVisibility
        ));
    }

    /**
     * Create a new event
     *
     * @Route("/admin/event/new", name="event_new")
     */
    public function newAction(Request $request)
    {
        $event = new Event();
        $event->setStartDate(new \DateTime('today'));
        $event->setEndDate(new \DateTime('tomorrow'));

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($event);
            $em->flush();

            return $this->redirect('/admin/event/list');
        }

        return $this->render('event/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Page for list of events
     *
     * @Route("/event/miss/{eid}", requirements={"eid": "\d+"}, name="event_miss")
     */
    public function eventNotFoundAction($eid)
    {
        return $this->render('event/public/miss.html.twig', array('eid' => $eid));
    }
}