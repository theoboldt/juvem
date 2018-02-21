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

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\AcquisitionAttributeFillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Export\Customized\Configuration;
use AppBundle\Export\Customized\CustomizedExport;
use AppBundle\Export\ParticipantsBirthdayAddressExport;
use AppBundle\Export\ParticipantsExport;
use AppBundle\Export\ParticipantsMailExport;
use AppBundle\Export\ParticipationsExport;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Twig\Extension\BootstrapGlyph;
use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminMultipleController extends Controller
{
    /**
     * Page for list of participants of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants", requirements={"eid": "\d+"}, name="event_participants_list")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsAction(Event $event)
    {
        return $this->render('event/participation/admin/participants-list.html.twig', array('event' => $event));
    }

    /**
     * Data provider for events participants list grid
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants.json", requirements={"eid": "\d+"}, name="event_participants_list_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsDataAction(Event $event, Request $request)
    {
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participantEntityList   = $participationRepository->participantsList($event, null, true, true);

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $statusFormatter = ParticipantStatus::formatter();

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
            $participantStatusText = $statusFormatter->formatMask($participantStatus);
            if ($participant->getDeletedAt()) {
                $participantStatusText .= ' <span class="label label-danger">gelöscht</span>';
            }
            $participantStatusWithdrawn = $participantStatus->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            $participantStatusRejected  = $participantStatus->has(ParticipantStatus::TYPE_STATUS_REJECTED);

            $price = $participant->getPrice(true);

            $participantEntry = array(
                'aid'                      => $participant->getAid(),
                'pid'                      => $participant->getParticipation()->getPid(),
                'is_deleted'               => (int)($participant->getDeletedAt() instanceof \DateTime),
                'is_paid'                  => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_PAID),
                'is_withdrawn'             => (int)$participantStatusWithdrawn,
                'is_rejected'              => (int)$participantStatusRejected,
                'is_withdrawn_or_rejected' => (int)($participantStatusWithdrawn || $participantStatusRejected),
                'is_confirmed'             => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_CONFIRMED),
                'payment_price'            => $price === null ? '<i>keiner</i>' : number_format($price, 2, ',', '.').' €',
                'nameFirst'                => $participant->getNameFirst(),
                'nameLast'                 => $participant->getNameLast(),
                'age'                      => $age,
                'phone'                    => implode(', ', $participantPhoneList),
                'status'                   => $participantStatusText,
                'gender'                   => $participant->getGender(true),
                'registrationDate'         => $participationDate->format(Event::DATE_FORMAT_DATE_TIME),
                'action'                   => $participantAction
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
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants/export", requirements={"eid": "\d+"}, name="event_participants_export")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportParticipantsAction(Event $event)
    {
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participantList         = $participationRepository->participantsList($event);

        $export = new ParticipantsExport(
            $this->get('app.twig_global_customization'), $event, $participantList, $this->getUser()
        );
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
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participations/export", requirements={"eid": "\d+"},
     *                                                    name="event_participations_export")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportParticipationsAction(Event $event)
    {
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participationsList      = $participationRepository->participationsList($event);

        $export = new ParticipationsExport(
            $this->get('app.twig_global_customization'), $event, $participationsList, $this->getUser()
        );
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
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants/birthday_address_export", requirements={"eid": "\d+"},
     *     name="event_participants_birthday_address_export")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportParticipantsBirthdayAddressAction(Event $event)
    {
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participantList         = $participationRepository->participantsList($event);

        $export = new ParticipantsBirthdayAddressExport($this->get('app.twig_global_customization'), $event, $participantList, $this->getUser());
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
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants_mail/export", requirements={"eid": "\d+"},
     *                                                    name="event_participants_mail_export")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportParticipantsMailAction(Event $event)
    {
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participantList         = $participationRepository->participantsList($event);
        $participationsList      = $participationRepository->participationsList($event);

        $export = new ParticipantsMailExport(
            $this->get('app.twig_global_customization'), $event, $participantList, $participationsList, $this->getUser()
        );
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
     * Apply changes to multiple participants
     *
     * @Route("/admin/event/participantschange", name="event_participants_change")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function activeButtonChangeStateHandler(Request $request)
    {
        $token        = $request->get('_token');
        $eid          = $request->get('eid');
        $action       = $request->get('action');
        $participants = filter_var_array($request->get('participants'), FILTER_VALIDATE_INT);

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('participants-list-edit' . $eid)) {
            throw new InvalidTokenHttpException();
        }

        $eventRepository         = $this->getDoctrine()->getRepository('AppBundle:Event');
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $event                   = $eventRepository->findOneBy(['eid' => $eid]);
        $this->denyAccessUnlessGranted('participants_edit', $event);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $participants         = $participationRepository->participantsList($event, $participants, true, true);
        $participationManager = $this->get('app.participation_manager');
        $em                   = $this->getDoctrine()->getManager();

        /** @var Participant $participant */
        foreach ($participants as $participant) {
            $participation = $participant->getParticipation();
            $changed = false;

            switch ($action) {
                case 'confirm':
                    if (!$participation->isConfirmed()) {
                        $participation->setIsConfirmed(true);
                        $participationManager->mailParticipationConfirmed($participation, $participation->getEvent());
                        $changed = true;
                    }
                    break;
                case 'paid':
                    if (!$participation->isPaid()) {
                        $participation->setIsPaid(true);
                    }
                    $changed = true;
                    break;
            }
            if ($changed) {
                $em->persist($participation);
            }
        }
        $em->flush();

        return new JsonResponse();
    }

    /**
     * Page for list of participants of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/export", requirements={"eid": "\d+"}, name="event_export_generator")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportGeneratorAction(Event $event)
    {
        $config = ['export' => ['participant' => ['nameFirst' => true, 'nameLast' => false]]];

        $processor     = new Processor();
        $configuration = new Configuration($event);
        $tree          = $configuration->getConfigTreeBuilder()->buildTree();

        $processedConfiguration = $processor->processConfiguration($configuration, $config);

        return $this->render('event/admin/export-generator.html.twig', array('event' => $event, 'config' => $tree->getChildren()));
    }

    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/export/process", name="event_export_generator_process")
     */
    public function exportGeneratorProcessAction(Request $request)
    {
        $token           = $request->get('_token');
        $eid             = $request->get('eid');
        $config          = $request->get('config');
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('export-generator-' . $eid)) {
            throw new InvalidTokenHttpException();
        }

        $event = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event || !is_array($config)) {
            throw new NotFoundHttpException('Transmitted event was not found');
        }
        $this->denyAccessUnlessGranted('participants_read', $event);
        $config = ['export' => $config]; //add root config option

        $processor     = new Processor();
        $configuration = new Configuration($event);

        $processedConfiguration = $processor->processConfiguration($configuration, $config);
        if (!$processedConfiguration['title']) {
            $processedConfiguration['title'] = 'Teilnehmer';
        }

        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participantList         = $participationRepository->participantsList($event);

            $export = new CustomizedExport(
            $this->get('app.twig_global_customization'),
            $event, $participantList,
            $this->getUser(),
            $processedConfiguration
        );
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
            $event->getTitle() . ' - '.$processedConfiguration['title'].'.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }

    /**
     * Page for list of participants of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants/print", requirements={"eid": "\d+"}, name="event_participants_print")
     * @Security("is_granted('participants_read', event)")
     */
    public function printParticipantsAction(Event $event)
    {
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participants            = $participationRepository->participantsList($event);

        return $this->render(
            'event/participation/admin/participants-print.html.twig',
            [
                'event'           => $event,
                'participants'    => $participants,
                'commentManager'  => $this->container->get('app.comment_manager'),
                'statusFormatter' => ParticipantStatus::formatter()
            ]
        );
    }
}
