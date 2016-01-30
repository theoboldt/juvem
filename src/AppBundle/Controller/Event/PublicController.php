<?php
namespace AppBundle\Controller\Event;


use AppBundle\Entity\Participation;
use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use AppBundle\Form\ParticipationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class PublicController extends Controller
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
		$participation->setEvent($event->getEid());
		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();

            $em->persist($participation);
			$em->flush();
			dump($em);
			dump($participation);
#            return $this->redirect('/event/' . $event->getEid());
		}

		return $this->render('event/public/participate.html.twig', array(
			'event' => $event,
			'form' => $form->createView()
		));
	}
}