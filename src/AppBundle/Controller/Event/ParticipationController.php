<?php

namespace AppBundle\Controller\Event;

use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ParticipationController extends Controller
{
    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants", name="event_participants_list")
     */
    public function listParticipantsAction($eid)
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirect('event_miss');
        }

        return $this->render('event/participants/list.html.twig', array('event' => $event));
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/admin/event/{eid}/participants.json", requirements={"eid": "\d+"}, name="event_participants_list_data")
     */
    public function listParticipantsDataAction(Request $request)
    {
        $eid = $request->get('eid');
        $eventRepository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirect('event_miss');
        }


        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT a
               FROM AppBundle:Participant a,
                    AppBundle:Participation p
              WHERE a.participation = p.pid
                AND p.event = :eid'
        )->setParameter('eid', $eid);
        $participantEntityList = $query->getResult();
dump($participantEntityList);
        $eventList = array();
        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {
            $eventList[] = array(
                'aid'       => $participant->getAid(),
                'nameFirst' => $participant->getNameFirst(),
                'nameLast'  => $participant->getNameLast(),
            );
        }


        return new JsonResponse($eventList);
    }

}
