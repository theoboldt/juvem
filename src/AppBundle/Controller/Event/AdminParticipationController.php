<?php

namespace AppBundle\Controller\Event;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\AcquisitionAttributeFillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Export\ParticipantsBirthdayAddressExport;
use AppBundle\Export\ParticipantsExport;
use AppBundle\Export\ParticipantsMailExport;
use AppBundle\Export\ParticipationsExport;
use AppBundle\Twig\Extension\BootstrapGlyph;
use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminParticipationController extends Controller
{
    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants", requirements={"eid": "\d+"}, name="event_participants_list")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listParticipantsAction($eid)
    {
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }


        $em                    = $this->getDoctrine()
                                      ->getManager();
        $query                 = $em->createQuery(
            'SELECT p
                   FROM AppBundle:Participation p
              WHERE p.event = :eid'
        )
                                    ->setParameter('eid', $eid);
        $participantEntityList = $query->getResult();

        return $this->render('event/participation/admin/participants-list.html.twig', array('event' => $event));
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/admin/event/{eid}/participants.json", requirements={"eid": "\d+"}, name="event_participants_list_data")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listParticipantsDataAction(Request $request)
    {
        $eid             = $request->get('eid');
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $participantEntityList = $eventRepository->participantsList($event, null, true, true);

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );

        $participantList = array();
        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {
            $participation        = $participant->getParticipation();
            $participationDate    = $participation->getCreatedAt();
            $participantPhoneList = array();

            /** @var PhoneNumber $phoneNumberEntity */
            foreach ($participation->getPhoneNumbers()
                                   ->getIterator() as $phoneNumberEntity) {
                /** @var \libphonenumber\PhoneNumber $phoneNumber */
                $phoneNumber            = $phoneNumberEntity->getNumber();
                $participantPhoneList[] = $phoneNumberUtil->formatOutOfCountryCallingNumber($phoneNumber, 'DE');
            }

            $participantAction = '';

            $participantStatus = $participant->getStatus(true);

            $age = number_format($participant->getAgeAtEvent(), 1, ',', '.');
            if ($participant->hasBirthdayAtEvent()) {
                $glyph = new BootstrapGlyph();
                $age .= ' ' . $glyph->bootstrapGlyph('gift');
            }

            $participantEntry = array(
                'aid'              => $participant->getAid(),
                'pid'              => $participant->getParticipation()
                                                  ->getPid(),
                'is_deleted'       => (int)($participant->getDeletedAt() instanceof \DateTime),
                'is_paid'          => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_PAID),
                'is_withdrawn'     => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN),
                'is_confirmed'     => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_CONFIRMED),
                'nameFirst'        => $participant->getNameFirst(),
                'nameLast'         => $participant->getNameLast(),
                'age'              => $age,
                'phone'            => implode(', ', $participantPhoneList),
                'status'           => $statusFormatter->formatMask($participantStatus),
                'gender'           => $participant->getGender(true),
                'registrationDate' => sprintf(
                    '<span style="display:none;">%s</span> %s', $participationDate->format('U'),
                    $participationDate->format(Event::DATE_FORMAT_DATE_TIME)
                ),
                'action'           => $participantAction
            );
            /** @var AcquisitionAttributeFillout $fillout */
            foreach ($participation->getAcquisitionAttributeFillouts() as $fillout) {
                $participantEntry['participation_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->__toString();
            }
            foreach ($participant->getAcquisitionAttributeFillouts() as $fillout) {
                $participantEntry['participant_acq_field_' . $fillout->getAttribute()->getBid()] = $fillout->__toString(
                );
            }

            $participantList[] = $participantEntry;
        }

        return new JsonResponse($participantList);
    }


    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participation/{pid}", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                  name="event_participation_detail")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function participationDetailAction(Request $request)
    {
        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $participation = $participationRepository->findOneBy(array('pid' => $request->get('pid')));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }
        $event = $participation->getEvent();

        $form = $this->createFormBuilder()
                     ->add('action', HiddenType::class)
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->get('action')
                           ->getData();
            switch ($action) {
                case 'delete':
                    $participation->setDeletedAt(new \DateTime());
                    break;
                case 'restore':
                    $participation->setDeletedAt(null);
                    break;
                case 'withdraw':
                    $participation->setIsWithdrawn(true);
                    break;
                case 'reactivate':
                    $participation->setIsWithdrawn(false);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($participation);
            $em->flush();
        }

        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );
        $foodFormatter = new LabelFormatter();

        $phoneNumberList = array();
        /** @var PhoneNumber $phoneNumberEntity */
        foreach ($participation->getPhoneNumbers()
                               ->getIterator() as $phoneNumberEntity) {
            /** @var \libphonenumber\PhoneNumber $phoneNumber */
            $phoneNumber       = $phoneNumberEntity->getNumber();
            $phoneNumberList[] = $phoneNumber;
        }

        return $this->render(
            'event/participation/admin/detail.html.twig',
            array('event'           => $event,
                  'participation'   => $participation,
                  'foodFormatter'   => $foodFormatter,
                  'statusFormatter' => $statusFormatter,
                  'phoneNumberList' => $phoneNumberList,
                  'form'            => $form->createView()
            )
        );
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/event/participation/confirm", name="event_participation_confirm_mail")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function participationConfirmAction(Request $request)
    {
        $token = $request->get('_token');
        $pid   = $request->get('pid');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken($pid)) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $participation = $participationRepository->findOneBy(array('pid' => $request->get('pid')));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }

        $participationManager = $this->get('app.participation_manager');
        $participationManager->mailParticipationConfirmed($participation, $participation->getEvent());

        return new JsonResponse(
            array(
                'success' => true
            )
        );
    }

    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants/export", requirements={"eid": "\d+"}, name="event_participants_export")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportParticipantsAction($eid)
    {
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');
        $participantList = $eventRepository->participantsList($event);

        $export = new ParticipantsExport($event, $participantList, $this->getUser());
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Teilnehmer.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }


    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participations/export", requirements={"eid": "\d+"},
     *                                                    name="event_participations_export")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportParticipationsAction($eid)
    {
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $eventRepository    = $this->getDoctrine()
                                   ->getRepository('AppBundle:Event');
        $participationsList = $eventRepository->participationsList($event);

        $export = new ParticipationsExport($event, $participationsList, $this->getUser());
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Anmeldungen.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }

    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants/birthday_address_export", requirements={"eid": "\d+"},
     *                                                    name="event_participants_birthday_address_export")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportParticipantsBirthdayAddressAction($eid)
    {
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $eventRepository    = $this->getDoctrine()
                                   ->getRepository('AppBundle:Event');
        $participantList    = $eventRepository->participantsList($event);

        $export = new ParticipantsBirthdayAddressExport($event, $participantList, $this->getUser());
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Teilnehmer.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }


    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants_mail/export", requirements={"eid": "\d+"},
     *                                                    name="event_participants_mail_export")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportParticipantsMailAction($eid)
    {
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $eventRepository    = $this->getDoctrine()
                                   ->getRepository('AppBundle:Event');
        $participantList    = $eventRepository->participantsList($event);
        $participationsList = $eventRepository->participationsList($event);

        $export = new ParticipantsMailExport($event, $participantList, $participationsList, $this->getUser());
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Anmeldungen.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }
}
