<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event\Participation;

use AppBundle\Controller\Event\WaitingListFlashTrait;
use AppBundle\Entity\Event;
use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\Participation;
use AppBundle\Form\ParticipationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PublicParticipateController extends Controller
{
    use WaitingListFlashTrait;

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/participate", requirements={"eid": "\d+"}, name="event_public_participate")
     */
    public function participateAction(Event $event, Request $request)
    {
        $eid = $event->getEid();

        if (!$event->isActive()) {
            $this->addFlash(
                'danger',
                'Bei der gewählte Veranstaltung werden im Moment keine Anmeldungen erfasst'
            );

            return $this->redirectToRoute('homepage', ['eid' => $eid]);
        }

        if ($request->getSession()->has('participation-' . $eid)) {
            /** @var Participation $participation */
            $participation = $request->getSession()
                                     ->get('participation-' . $eid);
            $sessionEvent  = $participation->getEvent();
            if ($sessionEvent->getEid() == $eid) {
                $participation->setEvent($event); //TODO improve solution
            } else {
                return $this->render(
                    'event/public/miss.html.twig', ['eid' => $eid],
                    new Response(null, Response::HTTP_NOT_FOUND)
                );

            }
        } else {
            $participation = new Participation($event);

            /** @var \AppBundle\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $participation->setNameLast($user->getNameLast());
                $participation->setNameFirst($user->getNameFirst());
            }
        }

        $form = $this->createForm(
            ParticipationType::class,
            $participation,
            [
                ParticipationType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipationType::ACQUISITION_FIELD_PRIVATE => false,
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set('participation-' . $eid, $participation);

            return $this->redirectToRoute('event_public_participate_confirm', ['eid' => $eid]);
        }
        $this->addWaitingListFlashIfRequired($event);

        $user           = $this->getUser();
        $participations = [];
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        return $this->render(
            'event/participation/public/begin.html.twig',
            [
                'event'                          => $event,
                'acquisitionFieldsParticipation' => $event->getAcquisitionAttributes(true, false, false, false, true),
                'participations'                 => $participations,
                'acquisitionFieldsParticipant'   => $event->getAcquisitionAttributes(false, true, false, false, true),
                'form'                           => $form->createView(),
            ]
        );
    }

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/participate/prefill/{pid}", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                  name="event_public_participate_prefill")
     */
    public function participatePrefillAction(Event $event, $pid, Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash(
                'warning',
                sprintf(
                    'Um Daten einer früherer Anmeldung verwenden zu können, müssen Sie angemeldet sein. Sie können sich jetzt <a href="%s">anmelden</a>, oder die Daten hier direkt eingeben.',
                    $this->generateUrl('fos_user_security_login')
                )
            );
            return $this->redirectToRoute('event_public_participate', ['eid' => $event->getEid()]);
        }

        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participationPrevious   = $participationRepository->findOneBy(
            ['pid' => $pid, 'assignedUser' => $user->getUid()]
        );
        if (!$participationPrevious) {
            $this->addFlash(
                'danger',
                'Es konnte keine passende Anmeldung von Ihnen gefunden werden, mit der das Anmeldeformular hätte vorausgefüllt werden können.'
            );
            return $this->redirectToRoute('event_public_participate', ['eid' => $event->getEid()]);
        }

        $participation = Participation::createFromTemplateForEvent($participationPrevious, $event);
        $participation->setAssignedUser($user);

        $request->getSession()->set('participation-' . $event->getEid(), $participation);
        $this->addFlash(
            'success',
            'Die Anmeldung wurde mit Daten einer früheren Anmeldung vorausgefüllt. Bitte überprüfen Sie sorgfältig ob die Daten noch richtig sind.'
        );
        return $this->redirectToRoute('event_public_participate', ['eid' => $event->getEid()]);
    }

    /**
     * Page for list of events
     *
     * @Route("/event/{eid}/participate/confirm", requirements={"eid": "\d+"}, name="event_public_participate_confirm")
     */
    public function confirmParticipationAction($eid, Request $request)
    {
        if (!$request->getSession()->has('participation-' . $eid)) {
            return $this->redirectToRoute('event_public_participate', ['eid' => $eid]);
        }

        /** @var Participation $participation */
        $participation = $request->getSession()->get('participation-' . $eid);
        $event         = $participation->getEvent();

        if (!$participation instanceof Participation
            || $eid != $participation->getEvent()->getEid()
        ) {
            throw new BadRequestHttpException('Given participation data is invalid');
        }

        if ($request->query->has('confirm')) {
            $user                 = $this->getUser();
            $managedParticipation = $this->get('app.participation_manager')->receiveParticipationRequest(
                $participation, $user
            );

            $participationManager = $this->get('app.participation_manager');
            $participationManager->mailParticipationRequested($participation, $event);

            $request->getSession()->remove('participation-' . $eid);

            if ($request->getSession()->has('participationList')) {
                $participationList = $request->getSession()->get('participationList');
            } else {
                $participationList = [];
            }
            $participationList[] = $managedParticipation->getPid();
            $request->getSession()
                    ->set('participationList', $participationList);

            $message
                = '<p>Wir haben Ihren Teilnahmewunsch festgehalten. Sie erhalten eine automatische Bestätigung, dass die Anfrage bei uns eingegangen ist.</p>';

            if (!$user) {
                $message .= sprintf(
                    '<p>Sie können sich jetzt <a href="%s">registrieren</a>. Dadurch können Sie Korrekturen an den Anmeldungen vornehmen oder zukünftige Anmeldungen schneller ausfüllen.</p>',
                    $this->container->get('router')->generate('fos_user_registration_register')
                );
            }
            $repositoryNewsletter = $this->getDoctrine()->getRepository(NewsletterSubscription::class);
            if (!$repositoryNewsletter->findOneByEmail($participation->getEmail())) {
                $message .= sprintf(
                    '<p>Sie können jetzt den <a href="%s">Newsletter abonnieren</a>, um auch in Zukunft von unseren Aktionen erfahren.</p>',
                    $this->container->get('router')->generate('newsletter_subscription')
                );
            }

            $this->addFlash(
                'success',
                $message
            );

            return $this->redirectToRoute('event_public_detail', ['eid' => $eid]);
        } else {
            $this->addWaitingListFlashIfRequired($event);
            return $this->render(
                'event/participation/public/confirm.html.twig',
                [
                    'participation' => $participation,
                    'event'         => $event,
                ]
            );
        }
    }
}
