<?php
namespace AppBundle\Controller\Event;


use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use AppBundle\Form\ParticipationType;
use AppBundle\ImageResponse;
use AppBundle\Manager\ParticipationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class PublicController extends Controller
{
    /**
     * Detail page for one single event
     *
     * @Route("/event/{eid}/image/{width}/{height}", requirements={"eid": "\d+", "width": "\d+", "height": "\d+"},
     *                                               name="event_image")
     */
    public function eventImageAction($eid, $width, $height)
    {
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirect('event_miss');
        }

        $uploadManager = $this->get('app.upload_image_manager');
        $image         = $uploadManager->fetchResized($event->getImageFilename(), $width, $height);

        return new ImageResponse($image);
    }


    /**
     * Page for details of an event
     *
     * @Route("/event/{eid}", requirements={"eid": "\d+"}, name="event_public_detail")
     */
    public function listAction($eid)
    {
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirectToRoute('event_miss', array('eid' => $eid));
        }

        return $this->render(
            'event/public/detail.html.twig', array(
                                               'event' => $event
                                           )
        );
    }

    /**
     * Active and visible events as list group
     *
     * @return Response
     */
    public function listActiveEventsAction()
    {
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');
        $eventList  = $repository->findBy(
            array('isVisible' => true,
                  'isActive'  => true
            ),
            array('startDate' => 'ASC',
                  'startTime' => 'ASC'
            )
        );

        return $this->render(
            'event/public/embed-list-group.html.twig', array(
                                                         'events' => $eventList
                                                     )
        );

    }

    /**
     * Active and visible events as list group
     *
     * @return Response
     */
    public function listActiveEventLinksAction()
    {
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');
        $eventList  = $repository->findBy(
            array('isVisible' => true,
                  'isActive'  => true
            ),
            array('startDate' => 'ASC',
                  'startTime' => 'ASC'
            )
        );

        return $this->render(
            'event/public/embed-link-list.html.twig', array(
                                                        'events' => $eventList
                                                    )
        );

    }
}