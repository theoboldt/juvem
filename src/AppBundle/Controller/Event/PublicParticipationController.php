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


class PublicParticipationController extends Controller
{

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

        if ($request->getSession()
                    ->has('participation-' . $eid)
        ) {
            /** @var Participation $participation */
            $participation = $request->getSession()
                                     ->get('participation-' . $eid);
            $sessionEvent  = $participation->getEvent();
            if ($sessionEvent->getEid() == $eid) {
                $event = $sessionEvent;
            } else {
                return $this->redirectToRoute('event_miss', array('eid' => $eid));
            }
        } else {
            $participation = new Participation();

            /** @var \AppBundle\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $participation->setNameLast($user->getNameLast());
                $participation->setNameFirst($user->getNameFirst());
            }
            $participation->setEvent($event);
        }
//dump($participation);
        $form = $this->createForm(ParticipationType::class, $participation);

        $form->handleRequest($request);
        if ($form->isValid() && $form->isSubmitted()) {
            $request->getSession()
                    ->set('participation-' . $eid, $participation);

            return $this->redirectToRoute('event_public_participate_confirm', array('eid' => $eid));
        }

        return $this->render(
            'event/public/participate-begin.html.twig', array(
                                                          'event' => $event,
                                                          'form'  => $form->createView()
                                                      )
        );
    }


    /**
     * Page for list of events
     *
     * @Route("/event/{eid}/participate/confirm", requirements={"eid": "\d+"}, name="event_public_participate_confirm")
     */
    public function confirmParticipationAction($eid, Request $request)
    {
        if (!$request->getSession()
                     ->has('participation-' . $eid)
        ) {
            return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
        }

        /** @var Participation $participation */
        $participation = $request->getSession()
                                 ->get('participation-' . $eid);
        $event         = $participation->getEvent();

        if (!$participation instanceof Participation
            || $eid != $participation->getEvent()
                                     ->getEid()
        ) {
            throw new BadRequestHttpException('Given participation data is invalid');
        }
//dump($participation);

        if ($request->query->has('confirm')) {
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($participation);
            $em->flush();

            /** @var Participant $participant */
            foreach ($participation->getParticipants() as $participant) {
                $participant->setParticipation($participation);
                $em->persist($participant);
                $em->flush();
            }
            /** @var PhoneNumber $participant */
            foreach ($participation->getPhoneNumbers() as $number) {
                $number->setParticipation($participation);
                $em->persist($number);
                $em->flush();
            }
            $participationManager = $this->get('app.participation_manager');
            $participationManager->mailParticipationRequested($participation, $event);

            $request->getSession()
                    ->remove('participation-' . $eid);

            $this->addFlash(
                'success',
                'Wir haben Ihren Teilnahmewunsch festgehalten. Sie erhalten eine automatische Bestätigung, dass die Anfrage bei uns eingegangen ist.'
            );

            return $this->redirectToRoute('event_public_detail', array('eid' => $eid));
        } else {
            return $this->render(
                'event/public/participate-confirm.html.twig', array(
                                                                'participation' => $participation,
                                                                'event'         => $event
                                                            )
            );
        }


    }
}