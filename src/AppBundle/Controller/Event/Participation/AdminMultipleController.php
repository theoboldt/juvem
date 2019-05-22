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
use AppBundle\Controller\Event\Gallery\GalleryPublicController;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\ExportTemplate;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Export\Customized\Configuration;
use AppBundle\Export\Customized\CustomizedExport;
use AppBundle\Export\ParticipantsBirthdayAddressExport;
use AppBundle\Export\ParticipantsExport;
use AppBundle\Export\ParticipantsMailExport;
use AppBundle\Export\ParticipationsExport;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Twig\Extension\BootstrapGlyph;
use AppBundle\Twig\Extension\PaymentInformation;
use DateTime;
use libphonenumber\PhoneNumberUtil;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
     * Page for list of participants of an event having a provided age at a specific date
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants-age", requirements={"eid": "\d+"}, name="event_participants_list_specific_age")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsWithSpecificAgeAction(Event $event)
    {
        return $this->render(
            'event/participation/admin/participants-list-specific-age.html.twig', ['event' => $event]
        );
    }

    /**
     * Data provider for events participants list grid having specific age at specific date
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants-specific-age.json", requirements={"eid": "\d+"}, name="event_participants_list_specific_age_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsWithSpecificAgeDataAction(Event $event, Request $request)
    {
        if (!$event->getSpecificDate()) {
            return new JsonResponse([]);
        }

        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participantEntityList   = $participationRepository->participantsList($event, null, true, true);

        $participantList = [];

        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {

            $yearsOfLife = EventRepository::yearsOfLife($participant->getBirthday(), $event->getSpecificDate());
            if ($yearsOfLife < $event->getSpecificAge()) {
                continue;
            }

            $age = $yearsOfLife;
            $age .= ' <span class="rounded-age">('
                    . number_format(EventRepository::age($participant->getBirthday(), $event->getSpecificDate()), 1, ',', '.')
                    . ')</span>';

            $birthday = $participant->getBirthday()->format('d.m.Y');
            if (EventRepository::hasBirthdayInTimespan($participant->getBirthday(), $event->getSpecificDate())) {
                $glyph    = new BootstrapGlyph();
                $birthday .= ' ' . $glyph->bootstrapGlyph('gift', 'Hat am Stichtag Geburtstag');
            }
            $participantEntry = [
                'aid'       => $participant->getAid(),
                'pid'       => $participant->getParticipation()->getPid(),
                'nameFirst' => $participant->getNameFirst(),
                'nameLast'  => $participant->getNameLast(),
                'age'       => $age,
                'birthday'  => $birthday,
                'gender'    => $participant->getGender(true),
            ];

            $participantList[] = $participantEntry;
        }

        return new JsonResponse($participantList);
    }
    
    /**
     * Navigate to other participation
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("expectedParticipation", class="AppBundle:Participation", options={"id" = "pid"})
     * @Route("/admin/event/{eid}/participation/{pid}/{direction}", requirements={"eid": "\d+", "pid": "\d+", "direction":"previous|next"}, name="admin_participation_navigate")
     * @param string $direction Either previous or next
     * @Security("is_granted('participants_read', event)")
     * @return Response
     */
    public function navigateParticipation(Event $event, Participation $expectedParticipation, string $direction)
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participants            = $participationRepository->participantsList($event, null, false, false);
        $participations          = [];
        
        /** @var Participant $participant */ //prepare ordered list
        foreach ($participants as $participant) {
            $participation                            = $participant->getParticipation();
            $participations[$participation->getPid()] = $participation;
        }
        $participations      = array_values($participations); //get rid of indexes, keep order
        $participationTarget = null;
        if (!count($participations)) {
            throw new NotFoundHttpException('Participations list seems to be empty');
        } elseif (count($participations) === 1) {
            $participationTarget = reset($participations);
        } else {
            foreach ($participations as $index => $participation) {
                if ($participation->getPid() === $expectedParticipation->getPid()) {
                    //found
                    if ($direction === 'previous') {
                        if (isset($participations[$index - 1])) {
                            $participationTarget = $participations[$index - 1];
                        } else {
                            $participationTarget = end($participations);
                        }
                    } else {
                        if (isset($participations[$index + 1])) {
                            $participationTarget = $participations[$index + 1];
                        } else {
                            $participationTarget = reset($participations);
                        }
                    }
                    break;
                }
            }
        }
        if (!$participationTarget) {
            throw new NotFoundHttpException('Failed to identify desired participation');
        }
        
        return $this->redirectToRoute(
            'event_participation_detail', ['eid' => $event->getEid(), 'pid' => $participationTarget->getPid()]
        );
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
        $includePayment          = ($request->query->has('payment'));
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participantEntityList   = $participationRepository->participantsList($event, null, true, true);
        $paymentManager          = $this->get('app.payment_manager');

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

            if ($includePayment) {
                $paymentStatus = $paymentManager->getParticipantPaymentStatus($participant);
            } else {
                $paymentStatus = null;
            }

            $participantAction = '';

            $participantStatus = $participant->getStatus(true);

            $age = $participant->getYearsOfLifeAtEvent();
            $age .= ' <span class="rounded-age">(' . number_format($participant->getAgeAtEvent(), 1, ',', '.') . ')</span>';
            if ($participant->hasBirthdayAtEvent()) {
                $glyph = new BootstrapGlyph();
                $age .= ' ' . $glyph->bootstrapGlyph('gift');
            }
            $participantStatusText = $statusFormatter->formatMask($participantStatus);
            if ($participant->getDeletedAt()) {
                $participantStatusText .= ' <span class="label label-danger">gelöscht</span>';
            }
            if ($paymentStatus) {
                $participantStatusText .= ' ' . PaymentInformation::provideLabel($paymentStatus);
            }
            $participantStatusWithdrawn = $participantStatus->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            $participantStatusRejected  = $participantStatus->has(ParticipantStatus::TYPE_STATUS_REJECTED);

            $basePrice = $participant->getBasePrice(true);

            $participantEntry = [
                'aid'                      => $participant->getAid(),
                'pid'                      => $participant->getParticipation()->getPid(),
                'is_paid'                  => null, //for tri state filter
                'is_deleted'               => (int)($participant->getDeletedAt() instanceof DateTime),
                'is_withdrawn'             => (int)$participantStatusWithdrawn,
                'is_rejected'              => (int)$participantStatusRejected,
                'is_withdrawn_or_rejected' => (int)($participantStatusWithdrawn || $participantStatusRejected),
                'is_confirmed'             => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_CONFIRMED),
                'payment_base_price'       => $basePrice === null
                    ? '<i>keiner</i>'
                    : number_format($basePrice, 2, ',', '.') . '&nbsp;€',
                'nameFirst'                => $participant->getNameFirst(),
                'nameLast'                 => $participant->getNameLast(),
                'age'                      => $age,
                'phone'                    => implode(', ', $participantPhoneList),
                'status'                   => $participantStatusText,
                'gender'                   => $participant->getGender(true),
                'registrationDate'         => $participationDate->format(Event::DATE_FORMAT_DATE_TIME),
                'action'                   => $participantAction
            ];

            if ($includePayment) {
                $toPay = $paymentStatus->getToPayValue(true);
                $price = $paymentStatus->getPrice( true);

                $participantEntry['payment_to_pay'] = $toPay === null
                    ? '<i>nichts</i>'
                    : number_format($toPay, 2, ',', '.') . '&nbsp;€';
                $participantEntry['payment_price']  = $price === null
                    ? '<i>keiner</i>'
                    : number_format($price, 2, ',', '.') . '&nbsp;€';
                $participantEntry['is_paid']        = (int)($paymentStatus->isPaid());
            }

            /** @var Fillout $fillout */
            foreach ($participation->getAcquisitionAttributeFillouts() as $fillout) {
                if (!$fillout->getAttribute()->isUseForParticipationsOrParticipants()) {
                    continue;
                }
                $participantEntry['participation_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
            }

            foreach ($participant->getAcquisitionAttributeFillouts() as $fillout) {
                if (!$fillout->getAttribute()->isUseForParticipationsOrParticipants()) {
                    continue;
                }
                $participantEntry['participant_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
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
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
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
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
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
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
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
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
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
     * @Route("/admin/event/participantschange", name="event_participants_change", methods={"POST"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function activeButtonChangeStateHandler(Request $request)
    {
        $token        = $request->get('_token');
        $eid          = $request->get('eid');
        $action       = $request->get('action');
        $message      = $request->get('message');
        $participants = filter_var_array($request->get('participants'), FILTER_VALIDATE_INT);

        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('participants-list-edit' . $eid)) {
            throw new InvalidTokenHttpException();
        }

        $eventRepository         = $this->getDoctrine()->getRepository(Event::class);
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        /** @var Event $event */
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
        $paymentManager       = $this->get('app.payment_manager');
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
                    $toPay = $paymentManager->getToPayValueForParticipant($participant, false);
                    if ($toPay > 0) {
                        $paymentManager->handlePaymentForParticipant($participant, ($toPay*-1), $message);
                    }
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
     * Update transmitted template
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("template", class="AppBundle:ExportTemplate", options={"id" = "id"})
     * @Route("/admin/event/{eid}/export/template/{id}/update", methods={"POST"}, requirements={"eid": "\d+", "id": "\d+"}, name="event_export_template_update")
     * @Security("is_granted('participants_read', event)")
     */
    public function updateTemplateConfigurationAction(Event $event, ExportTemplate $template, Request $request)
    {
        $em            = $this->getDoctrine()->getManager();
        $configuration = $this->processRequestConfiguration($request);
        $template->setConfiguration($configuration);
        $template->setModifiedAtNow();
        $template->setModifiedBy($this->getUser());
        $em->persist($template);
        $em->flush();

        return $this->redirectToRoute('event_export_generator', ['eid' => $event->getEid()]);
    }

    /**
     * Create transmitted template
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/export/template/create", methods={"POST"}, requirements={"eid": "\d+"}, name="event_export_template_create")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @param Request $request
     * @return RedirectResponse
     */
    public function createTemplateConfigurationAction(Event $event, Request $request)
    {
        $templates = $this->getDoctrine()->getRepository(ExportTemplate::class)->templateCount();

        $configuration = $this->processRequestConfiguration($request);
        $template      = new ExportTemplate($event, $event->getTitle() . ' Export #' . ($templates + 1), null, $configuration);
        $template->setCreatedBy($this->getUser());
        $em            = $this->getDoctrine()->getManager();
        $em->persist($template);
        $em->flush();

        return $this->redirectToRoute('event_export_generator', ['eid' => $event->getEid()]);
    }

    /**
     * Page for list of participants of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/export", requirements={"eid": "\d+"}, name="event_export_generator")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function exportGeneratorAction(Event $event, Request $request)
    {
        $templates = $this->getDoctrine()->getRepository(ExportTemplate::class)->findSuitableForEvent($event);
        $em        = $this->getDoctrine()->getManager();

        $formEditTemplate   = $this->createFormBuilder()
                                   ->add('edit', HiddenType::class)
                                   ->add('title', TextType::class, ['label'=> 'Titel'])
                                   ->add('description', TextareaType::class, ['label' => 'Beschreibung'])
                                   ->getForm();
        $formDeleteTemplate = $this->createFormBuilder()
                                   ->add('delete', HiddenType::class)
                                   ->getForm();
            $redirect = false;

        $formEditTemplate->handleRequest($request);
        if ($formEditTemplate->isSubmitted() && $formEditTemplate->isValid()) {
            $template = $em->find(ExportTemplate::class, $formEditTemplate->get('edit')->getData());
            $template->setTitle($formEditTemplate->get('title')->getData());
            $template->setDescription($formEditTemplate->get('description')->getData());
            $template->setModifiedAtNow();
            $template->setModifiedBy($this->getUser());
            $em->persist($template);
            $em->flush();
            $redirect = true;
        }

        $formDeleteTemplate->handleRequest($request);
        if ($formDeleteTemplate->isSubmitted() && $formDeleteTemplate->isValid()) {
            $template = $em->find(ExportTemplate::class, $formDeleteTemplate->get('delete')->getData());
            if ($template instanceof ExportTemplate) {
                $em->remove($template);
                $em->flush();
                $redirect = true;
            }
        }
        if ($redirect) {
            return $this->redirectToRoute('event_export_generator', ['eid' => $event->getEid()]);
        }

        $config = ['export' => ['participant' => ['nameFirst' => true, 'nameLast' => false]]];

        $processor     = new Processor();
        $configuration = new Configuration($event);
        $tree          = $configuration->getConfigTreeBuilder()->buildTree();


        $processedConfiguration = $processor->processConfiguration($configuration, $config);

        return $this->render(
            'event/admin/export-generator.html.twig',
            [
                'event'              => $event,
                'config'             => $tree->getChildren(),
                'templates'          => $templates,
                'formDeleteTemplate' => $formDeleteTemplate->createView(),
                'formEditTemplate'   => $formEditTemplate->createView()
            ]
        );
    }

    /**
     * Process transmitted configuration and provide download url
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/export/download/{filename}", requirements={"eid": "\d+", "filename": "([a-zA-Z0-9\s_\\.\-\(\):])+"}, name="event_export_generator_process")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportGeneratorProcessDirectAction(Event $event, Request $request)
    {
        $result = $this->generateExport($request);

        $url = $this->get('router')->generate(
            'event_export_generator_download',
            [
                'eid'      => $event->getEid(),
                'tmpname'  => basename($result['path']),
                'filename' => $result['name']
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return JsonResponse::create(['download_url' => $url]);
    }


    /**
     * Download created export
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/export/download/{tmpname}/{filename}", requirements={"eid": "\d+", "tmpname": "([a-zA-Z0-9\s_\\.\-\(\):])+", "filename": "([a-zA-Z0-9\s_\\.\-\(\):])+"}, name="event_export_generator_download")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportGeneratedDownloadAction(Event $event, string $tmpname, string $filename, Request $request) {
        $path = $this->getParameter('app.tmp.root.path').'/'.$tmpname;
        if (!file_exists($path)) {
            throw new NotFoundHttpException('Requested export '.$path.' not found');
        }
        $response = new BinaryFileResponse($path);

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $d);

        //ensure file deleted after request
        $this->get('event_dispatcher')->addListener(
            KernelEvents::TERMINATE,
            function (PostResponseEvent $event) use ($path) {
                if (file_exists($path)) {
                    usleep(100);
                    unlink($path);
                }
            }
        );

        return $response;
    }


    /**
     * Page for list of participants of an event
     *
     * @deprecated
     * @Route("/admin/event/export/process", name="event_export_generator_process_legacy")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportGeneratorProcessAction(Event $event, Request $request)
    {
        $result = $this->generateExport($request);

        $response = new BinaryFileResponse($result['path']);

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $result['name']
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }

    /**
     * Process configuration from request and provide result as array
     *
     * @param Request $request
     * @return array
     */
    private function processRequestConfiguration(Request $request): array
    {
        $token           = $request->get('_token');
        $eid             = $request->get('eid');
        $config          = $request->get('config');
        $eventRepository = $this->getDoctrine()->getRepository(Event::class);

        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('export-generator-' . $eid)) {
            throw new InvalidTokenHttpException();
        }

        /** @var Event $event */
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
        return $processedConfiguration;
    }

    /**
     * Generate export file and provide file info
     *
     * @param Request $request
     * @return array
     */
    private function generateExport(Request $request): array
    {
        $processedConfiguration = $this->processRequestConfiguration($request);

        $eventRepository = $this->getDoctrine()->getRepository(Event::class);
        /** @var Event $event */
        $event = $eventRepository->findOneBy(['eid' => $request->get('eid')]);

        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $paymentManager = $this->get('app.payment_manager');

        $filterConfirmed         = $processedConfiguration['filter']['confirmed'];
        $filterPaid              = $processedConfiguration['filter']['paid'];
        $filterRejectedWithdrawn = $processedConfiguration['filter']['rejectedwithdrawn'];
        $participantList         = array_filter(
            $participationRepository->participantsList(
                $event,
                null,
                false,
                ($filterRejectedWithdrawn !== Configuration::OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN)
            ),
            function (Participant $participant) use ($paymentManager, $filterConfirmed, $filterPaid, $filterRejectedWithdrawn) {
                $include = true;
                switch ($filterConfirmed) {
                    case Configuration::OPTION_CONFIRMED_CONFIRMED:
                        $include = $include && $participant->isConfirmed();
                        break;
                    case Configuration::OPTION_CONFIRMED_UNCONFIRMED:
                        $include = $include && !$participant->isConfirmed();
                        break;
                }
                switch ($filterPaid) {
                    case Configuration::OPTION_PAID_PAID:
                        $include = $include && !$paymentManager->isParticipantRequiringPayment($participant);
                        break;
                    case Configuration::OPTION_PAID_NOTPAID:
                        $include = $include && $paymentManager->isParticipantRequiringPayment($participant);
                        break;
                }
                switch($filterRejectedWithdrawn) {
                    case Configuration::OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN:
                        $include = $include && !($participant->isRejected() || $participant->isWithdrawn());
                        break;
                    case Configuration::OPTION_REJECTED_WITHDRAWN_REJECTED_WITHDRAWN:
                        $include = $include && ($participant->isRejected() || $participant->isWithdrawn());
                        break;
                }

                return $include;
            }
        );

        $export = new CustomizedExport(
            $this->get('app.twig_global_customization'),
            $this->get('app.payment_manager'),
            $event,
            $participantList,
            $this->getUser(),
            $processedConfiguration
        );
        $tmpPath = tempnam($this->getParameter('app.tmp.root.path'), 'export_');
        if (!$tmpPath) {
            throw new RuntimeException('Failed to create tmp file');
        }
        $export->setMetadata();
        $export->process();
        $export->write($tmpPath);

        //filter name
        $filename = $event->getTitle() . ' - ' . $processedConfiguration['title'] . '.xlsx';
        $filename = preg_replace('/[^\x20-\x7e]{1}/', '', $filename);

        return [
            'path' => $tmpPath,
            'name' => $filename
        ];
    }

    /**
     * Page for list of participants of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants/{type}", requirements={"eid": "\d+", "type": "(print|printdataonly)"}, name="event_participants_print")
     * @Security("is_granted('participants_read', event)")
     */
    public function printParticipantsAction(Event $event, $eid, $type)
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participants            = $participationRepository->participantsList($event);

        return $this->render(
            'event/participation/admin/participants-print.html.twig',
            [
                'event'           => $event,
                'participants'    => $participants,
                'commentManager'  => $this->container->get('app.comment_manager'),
                'type'            => $type,
                'statusFormatter' => ParticipantStatus::formatter(),
            ]
        );
    }
    
    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants/profile_export.docx", requirements={"eid": "\d+"}, name="event_participants_profile")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @return Response
     */
    public function generateParticipantsProfileAction(Event $event) {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participants            = $participationRepository->participantsList($event);
    
        $generator = $this->get('app.participant.profile_generator');
        $path      = $generator->generate($participants);
        $response  = new BinaryFileResponse($path);

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            preg_replace('/[^a-zA-Z0-9\-\._ ]/','', $event->getTitle().' Profile.docx')
        );
        $response->headers->set('Content-Disposition', $d);

        //ensure file deleted after request
        $this->get('event_dispatcher')->addListener(
            KernelEvents::TERMINATE,
            function (PostResponseEvent $event) use ($path) {
                if (file_exists($path)) {
                    usleep(100);
                    unlink($path);
                }
            }
        );

        return $response;
    }

    /**
     * Page for list of participants of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participate-timeline.json", requirements={"eid": "\d+"}, name="event_participate_timeline")
     * @Security("is_granted('read', event)")
     */
    public function provideParticipateTimelineDataAction(Event $event)
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $newParticipants = [];
        /** @var Participant $participant */
        foreach ($participationRepository->participantsList($event) as $participant) {
            $date = $participant->getCreatedAt()->format('Y-m-d');
            if (!isset($newParticipants[$date])) {
                $newParticipants[$date] = 0;
            }
            $newParticipants[$date] += 1;
        }

        ksort($newParticipants);
        reset($newParticipants);
        $startDate   = new DateTime(key($newParticipants) . ' 10:00:00');
        $currentDate = clone $startDate;

        $endDate = clone $event->getStartDate();
        $endDate->setTime(10, 0);

        $today = new DateTime();
        $today->setTime(10, 0);

        $days = 0;
        $countBefore = 0;
        $countCurrent = 0;
        $history     = [];
        while($currentDate <= $endDate && $currentDate < $today) {
            $countCurrent = $countBefore;

            $date = $currentDate->format('Y-m-d');
            $diff = $currentDate->diff($endDate);

            if (isset($newParticipants[$date])) {
                $countCurrent += $newParticipants[$date];
            }

            $year  = $currentDate->format('Y');
            $month = GalleryPublicController::convertMonthNumber((int)$currentDate->format('n'));
            $day   = $currentDate->format('d');
            if (!isset($history[$year])) {
                $history[$year] = [];
            }
            if (!isset($history[$year][$month])) {
                $history[$year][$month] = [];
            }
            $history[$year][$month][] = [
                'day'   => $day,
                'count' => $countCurrent,
                'days'  => $diff->days
            ];


            $countBefore = $countCurrent;
            $currentDate->modify('+1 day');
            ++$days;
        }

        return new JsonResponse(['history' => $history, 'participantsTotal' => $countCurrent, 'daysTotal' => $days]);
    }
}
