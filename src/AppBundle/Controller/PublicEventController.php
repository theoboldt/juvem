<?php
namespace AppBundle\Controller;


use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class PublicEventController extends Controller
{

    /**
     * Page for list of events
     *
     * @Route("/event/{eid}", requirements={"eid": "\d+"}, name="event_public_detail")
     */
    public function listAction($eid)
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));

        return $this->render('event/public/detail.html.twig', array(
            'event' => $event
        ));
    }

}