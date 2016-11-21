<?php
namespace AppBundle\Controller\Event;


use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\ParticipationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );

        }
        if (!$event->isActive()) {
            $this->addFlash(
                'danger',
                'Bei der gewählte Veranstaltung werden im Moment keine Anmeldungen erfasst'
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
                return $this->render(
                    'event/public/miss.html.twig', array('eid' => $eid),
                    new Response(null, Response::HTTP_NOT_FOUND)
                );

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

        $form = $this->createForm(ParticipationType::class, $participation);

        $form->handleRequest($request);
        if ($form->isValid() && $form->isSubmitted()) {
            $request->getSession()
                    ->set('participation-' . $eid, $participation);

            return $this->redirectToRoute('event_public_participate_confirm', array('eid' => $eid));
        }

        $user           = $this->getUser();
        $participations = array();
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        return $this->render(
            'event/participation/public/begin.html.twig',
            array(
                'event'                          => $event,
                'acquisitionFieldsParticipation' => $event->getAcquisitionAttributes(true, false),
                'participations'                 => $participations,
                'acquisitionFieldsParticipant'   => $event->getAcquisitionAttributes(false, true),
                'form'                           => $form->createView()
            )
        );
    }

    /**
     * Page for list of events
     *
     * @Route("/event/{eid}/participate/prefill/{pid}", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                  name="event_public_participate_prefill")
     */
    public function participatePrefillAction($eid, $pid, Request $request)
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
            return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
        }

        $em                      = $this->getDoctrine()->getManager();
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participationPrevious   = $participationRepository->findOneBy(
            array('pid' => $pid, 'assignedUser' => $user->getUid())
        );
        if (!$participationPrevious) {
            $this->addFlash(
                'danger',
                'Es konnte keine passende Anmeldung von Ihnen gefunden werden, mit der das Anmeldeformular hätte vorausgefüllt werden können.'
            );
            return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
        }

        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $event           = $eventRepository->findOneBy(array('eid' => $eid));

        $participation = Participation::createFromTemplateForEvent($participationPrevious, $event);
        $participation->setAssignedUser($user);

        $request->getSession()->set('participation-' . $eid, $participation);
        $this->addFlash(
            'success',
            'Die Anmeldung wurde mit Daten einer früheren Teilnahme vorausgefüllt. Bitte überprüfen Sie sorgfältig ob die Daten noch richtig sind.'
        );
        return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
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

        if ($request->query->has('confirm')) {
            $em = $this->getDoctrine()
                       ->getManager();
            if ($event->getIsAutoConfirm()) {
                $participation->setIsConfirmed(true);
            }
            $managedParticipation = $em->merge($participation);

            $em->persist($managedParticipation);
            $em->flush();

            $participationManager = $this->get('app.participation_manager');
            $participationManager->mailParticipationRequested($participation, $event);

            $request->getSession()
                    ->remove('participation-' . $eid);

            if ($request->getSession()
                        ->has('participationList')
            ) {
                $participationList = $request->getSession()
                                             ->get('participationList');
            } else {
                $participationList = array();
            }
            $participationList[] = $managedParticipation->getPid();
            $request->getSession()
                    ->set('participationList', $participationList);

            $message
                = '<p>Wir haben Ihren Teilnahmewunsch festgehalten. Sie erhalten eine automatische Bestätigung, dass die Anfrage bei uns eingegangen ist.</p>';

            if (!$this->getUser()) {
                $message .= sprintf(
                    '<p>Sie können sich jetzt <a href="%s">registrieren</a>. Dadurch können Sie Korrekturen an den Anmeldungen zur Teilnahme vornehmen oder zukünftige Anmeldungen schneller ausfüllen.</p>',
                    $this->container->get('router')->generate('fos_user_registration_register')
                );
            }
            $repositoryNewsletter = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');
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

            return $this->redirectToRoute('event_public_detail', array('eid' => $eid));
        } else {
            return $this->render(
                'event/participation/public/confirm.html.twig', array(
                                                                  'participation' => $participation,
                                                                  'event'         => $event
                                                              )
            );
        }
    }

    /**
     * Page for list of events
     *
     * @Route("/participation", name="public_participations")
     * @Security("has_role('ROLE_USER')")
     */
    public function listParticipationsAction()
    {
        return $this->render('event/participation/public/participations-list.twig');
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/participations.json", name="public_participations_list_data")
     * @Security("has_role('ROLE_USER')")
     */
    public function listParticipantsDataAction(Request $request)
    {
        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );

        $dateFormatDay     = 'd.m.y';
        $dateFormatDayHour = 'd.m.y H:i';

        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $user = $this->getUser();

        $participationList = $participationRepository->findBy(array('assignedUser' => $user->getUid()));

        $participationListResult = array();
        /** @var Participant $participant */
        foreach ($participationList as $participation) {
            $event = $participation->getEvent();


            $eventStartDate = $event->getStartDate()->format(Event::DATE_FORMAT_DATE);
            if ($event->hasEndDate()) {
                $eventEndDate = $event->getEndDate()->format(Event::DATE_FORMAT_DATE);
            } else {
                $eventEndDate = $eventStartDate;
            }
            if ($event->hasStartTime()) {
                $eventStartDate .= ' ' . $event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            }
            if ($event->hasEndTime()) {
                $eventEndDate .= ' ' . $event->getEndTime()->format(Event::DATE_FORMAT_TIME);
            } elseif ($event->hasStartTime()) {
                $eventEndDate .= ' ' . $event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            }

            $eventTime = sprintf(
                '%s - %s',
                $eventStartDate,
                $eventEndDate
            );

            $participantsString = '';
            foreach ($participation->getParticipants() as $participant) {
                $participantsString .= sprintf(
                    ' %s %s', $participant->getNameFirst(), $statusFormatter->formatMask($participant->getStatus(true))
                );
            }

            $participationListResult[] = array(
                'pid'          => $participation->getPid(),
                'eventTitle'   => $event->getTitle(),
                'eventTime'    => $eventTime,
                'participants' => $participantsString
            );
        }

        return new JsonResponse($participationListResult);
    }


    /**
     * Page for list of events
     *
     * @Route("/participation/{pid}", requirements={"pid": "\d+"}, name="public_participation_detail")
     * @Security("has_role('ROLE_USER')")
     */
    public function participationDetailedAction(Request $request)
    {

        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );

        $user          = $this->getUser();
        $repository    = $this->getDoctrine()
                              ->getRepository('AppBundle:Participation');
        $participation = $repository->findOneBy(array('pid' => $request->get('pid')));

        if ($participation->getAssignedUser()
                          ->getUid() != $user->getUid()
        ) {
            throw new AccessDeniedHttpException('Participation is related to another user');
        }

        $form = $this->createFormBuilder()
                     ->add('aid', HiddenType::class)
                     ->add('action', HiddenType::class)
                     ->add('value', HiddenType::class)
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action   = $form->get('action')->getData();
            $aid      = $form->get('aid')->getData();
            $newValue = !$form->get('value')->getData();

            /** @var Participant $participationParticipant */
            foreach ($participation->getParticipants() as $participationParticipant) {
                if ($participationParticipant->getAid() == $aid) {
                    $participant = $participationParticipant;
                }
            }
            if (isset($participant)) {
                switch ($action) {
                    case 'withdraw':
                        if ($participant->isWithdrawn()) {
                            if ($newValue) {
                                $this->addFlash(
                                    'success',
                                    'Die Anmeldung wurde von uns bereits als zurückgezogen markiert. Es ist keine weitere Aktion nötig.'
                                );
                            } else {
                                $this->addFlash(
                                    'danger',
                                    'Die Anmeldung wurde von uns bereits als zurückgezogen markiert. Wenn Sie die Anmeldung diesen Teilnehmers reaktivieren möchten, wenden Sie sich in diesem Fall bitte direkt an das Jugendwerk.'
                                );
                            }
                        } else {
                            $participant->setIsWithdrawRequested($newValue);
                            $em                   = $this->getDoctrine()->getManager();
                            $managedParticipation = $em->merge($participation);

                            $em->persist($managedParticipation);
                            $em->flush();
                            if ($newValue) {
                                $this->addFlash(
                                    'success',
                                    'Ihre Anfrage zur Zurücknahme dieser Anmeldung wurde registiert und wird demnächst von uns bearbeitet. Wenn sich der Status der Anmeldung des betroffenen Teilnehmers nicht innerhalb einiger Tage ändert, wenden Sie sich bitte direkt an das Jugendwerk.'
                                );
                            } else {
                                $this->addFlash(
                                    'success',
                                    'Sie haben ihre Anfrage auf Zurücknahme dieser Anmeldung entfernt. Die Anmeldung ist damit wieder gültig.'
                                );
                            }
                        }
                        break;

                }

                return $this->redirectToRoute('public_participation_detail', array('pid' => $participation->getPid()));
            }

        }

        return $this->render(
            'event/participation/public/detail.html.twig', array(
                                                             'form'            => $form->createView(),
                                                             'participation'   => $participation,
                                                             'event'           => $participation->getEvent(),
                                                             'statusFormatter' => $statusFormatter
                                                         )
        );

    }
}