<?php
namespace AppBundle\Controller;


use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class EventController extends Controller
{

    /**
     * Page for list of events
     *
     * @Route("/event/list", name="event_list")
     */
    public function listAction()
    {
        return $this->render('event/list.html.twig');
    }

    /**
     * Data provider for event list grid
     *
     * @Route("/event/list.json")
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
        $eventEntityList    = $repository->findAll();

        $dateFormatDay = 'd.m.y';
        $dateFormatDayHour = 'd.m.y H:i';
        $glyphicon  = '<span class="glyphicon glyphicon-%s" aria-hidden="true"></span> ';

        $eventList  = array();
        /** @var Event $event */
        foreach($eventEntityList as $event) {
            $eventStatus    = '';
            if ($event->isVisible()) {
                $eventStatus    .= sprintf($glyphicon, 'eye-open');
            } else {
                $eventStatus    .= sprintf($glyphicon, 'eye-close');
            }

            if ($event->isActive()) {
                $eventStatus    .= sprintf($glyphicon, 'folder-open');
            } else {
                $eventStatus    .= sprintf($glyphicon, 'folder-close');
            }

            $eventStartFormat   = $dateFormatDayHour;
            if ($event->getStartDate()->format('Hi') == '0000') {
                $eventStartFormat = $dateFormatDay;
            }
            $eventEndFormat   = $dateFormatDayHour;
            if ($event->getEndDate()->format('Hi') == '0000') {
                $eventEndFormat = $dateFormatDay;
            }

            $eventList[]    = array(
                'eid' => $event->getEid(),
                'title' => $event->getTitle(),
                'description' => $event->getTitle(),
                'start_date' => $event->getStartDate()->format($eventStartFormat),
                'end_date' => $event->getEndDate()->format($eventEndFormat),
                'status' => $eventStatus
            );
        }


        return new JsonResponse($eventList);
    }


    /**
     * Detail page for one single event
     *
     * @Route("/event/{eid}", requirements={"eid": "\d+"}, name="event")
     */
    public function listEventAction($eid)
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));

        $eventDeleteModal   = array(
            'id' => 'evenDeleteModal',
            'title' => 'Ereignis löschen',
            'message' => 'Soll dieses Ereignis wirklich gelöscht werden?',
            'action' => 'Löschen',
            'form' =>  $this->createForm(ModalActionType::class, array('id' => $eid, 'area' => 'event'))->createView()
        );

        return $this->render('event/detail.html.twig', array(
            'event' => $event,
            'eventDeleteModal' => $eventDeleteModal
        ));
    }

    /**
     * Create a new event
     *
     * @Route("/event/new", name="event_new")
     */
    public function newAction(Request $request)
    {
        $event = new Event();
        $event->setStartDate(new \DateTime('today'));
        $event->setEndDate(new \DateTime('tomorrow'));

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);

        if ($form->isValid()) {
            dump($form->getData());
            $em = $this->getDoctrine()->getManager();

            $em->persist($event);
            $em->flush();

            return $this->redirect('/event/list');
        }

        return $this->render('event/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

}