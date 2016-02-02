<?php
namespace AppBundle\Controller\Event;


use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use AppBundle\Form\ParticipationType;
use AppBundle\Manager\ParticipationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class PublicController extends Controller
{

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

        return $this->render('event/public/detail.html.twig', array(
            'event' => $event
        ));
    }

    /**
     * Page for list of events
     *
     * @Route("/event/{eid}/participate", requirements={"eid": "\d+"}, name="event_public_participate")
     */
    public function participateAction(Request $request)
    {
        $eid = $request->get('eid');

        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirectToRoute('event_miss', array('eid' => $eid));
        }
        if (!$event->isActive()) {
            $this->addFlash(
                'danger',
                'Die gewählte Veranstaltung ist nicht aktiv'
            );

            return $this->redirectToRoute('homepage', array('eid' => $eid));
        }

        $participation = new Participation();

        /** @var \AppBundle\Entity\User $user */
        $user = $this->getUser();
        if ($user) {
            $participation->setNameLast($user->getNameLast());
            $participation->setNameFirst($user->getNameFirst());
        }

        $form = $this->createForm(ParticipationType::class, $participation);

        $form->handleRequest($request);
        $participation->setEvent($event);
        if ($form->isValid() && $form->isSubmitted()) {
            $request->getSession()->set('participation', $participation);

            return $this->redirectToRoute('event_public_participate_confirm', array('eid' => $eid));
        }

        return $this->render('event/public/participate.html.twig', array(
            'event' => $event,
            'form'  => $form->createView()
        ));
    }


    /**
     * Page for list of events
     *
     * @Route("/event/{eid}/participate/confirm", requirements={"eid": "\d+"}, name="event_public_participate_confirm")
     */
    public function confirmParticipationAction($eid, Request $request)
    {
        if (!$request->getSession()->has('participation')) {
            return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
        }

        /** @var Participation $participation */
        $participation = $request->getSession()->get('participation');
        $event = $participation->getEvent();

        if (!$participation instanceof Participation
            || $eid != $participation->getEvent()->getEid()
        ) {
            throw new BadRequestHttpException('Given participation data is invalid');
        }

        if ($request->query->has('confirm')) {
//            $em = $this->getDoctrine()->getManager();
//
//            $em->persist($participation);
//            $em->flush();
//
//            /** @var Participant $participant */
//            foreach ($participation->getParticipants() as $participant) {
//                $participant->setParticipation($participation);
//                $em->persist($participant);
//                $em->flush();
//            }
//            /** @var PhoneNumber $participant */
//            foreach ($participation->getPhoneNumbers() as $number) {
//                $number->setParticipation($participation);
//                $em->persist($number);
//                $em->flush();
//            }
//
//            $this->addFlash(
//                'success',
//                'Wir haben Ihren Teilnahmewunsch festgehalten. Sie erhalten eine automatische Bestätigung, dass die Anfrage bei uns eingegangen ist.'
//            );
//

            $participationManager = $this->get('app.participation_manager');
            //$participationManager->mailParticipationRequested($participation, $event);

            //confirmed
            $request->getSession()->remove('participation');
        } else {
            return $this->render('event/public/participate-confirm.html.twig', array(
                'participation' => $participation,
                'event' => $event
            ));
        }


    }
}