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

use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\ExportTemplate;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationRepository;
use AppBundle\Export\Customized\Configuration as ExcelConfiguration;
use AppBundle\Export\Customized\CustomizedExport;
use AppBundle\Export\ParticipantsBirthdayAddressExport;
use AppBundle\Export\ParticipantsExport;
use AppBundle\Export\ParticipantsMailExport;
use AppBundle\Export\ParticipationsExport;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Manager\ParticipantProfile\Configuration as WordConfiguration;
use AppBundle\ResponseHelper;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
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

class AdminMultipleExportController extends Controller
{
    /**
     * Generate export wizard
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants/export", requirements={"eid": "\d+"}, name="event_participants_export")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportExcelParticipantsAction(Event $event)
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
        ResponseHelper::configureAttachment(
            $response,
            $event->getTitle() . ' - Teilnehmer.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        return $response;
    }

    /**
     * Generate participations export
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participations/export", requirements={"eid": "\d+"},
     *                                                    name="event_participations_export")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportExcelParticipationsAction(Event $event)
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
        ResponseHelper::configureAttachment(
            $response,
            $event->getTitle() . ' - Anmeldungen.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

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
    public function exportExcelParticipantsBirthdayAddressAction(Event $event)
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
        ResponseHelper::configureAttachment(
            $response,
            $event->getTitle() . ' - Teilnehmer.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

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
    public function exportExcelParticipantsMailAction(Event $event)
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
        ResponseHelper::configureAttachment(
            $response,
            $event->getTitle() . ' - Anmeldungen.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        return $response;
    }

    /**
     * Update transmitted template
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("template", class="AppBundle:ExportTemplate", options={"id" = "id"})
     * @Route("/admin/event/{eid}/export/template/{id}/update", methods={"POST"}, requirements={"eid": "\d+", "id": "\d+"}, name="event_export_template_update")
     * @Security("is_granted('participants_read', event)")
     */
    public function updateExcelTemplateConfigurationAction(Event $event, ExportTemplate $template, Request $request)
    {
        $em            = $this->getDoctrine()->getManager();
        $configuration = $this->processRequestConfiguration($request, ExcelConfiguration::class);
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
    public function createExcelTemplateConfigurationAction(Event $event, Request $request)
    {
        $templates = $this->getDoctrine()->getRepository(ExportTemplate::class)->templateCount();

        $configuration = $this->processRequestConfiguration($request, ExcelConfiguration::class);
        $template      = new ExportTemplate($event, $event->getTitle() . ' Export #' . ($templates + 1), null, $configuration);
        $template->setCreatedBy($this->getUser());
        $em            = $this->getDoctrine()->getManager();
        $em->persist($template);
        $em->flush();

        return $this->redirectToRoute('event_export_generator', ['eid' => $event->getEid()]);
    }

    /**
     * Page for excel export generation wizard
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/export", requirements={"eid": "\d+"}, name="event_export_generator")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function exportExcelGeneratorAction(Event $event, Request $request)
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
        $configuration = new ExcelConfiguration($event);
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
     * Page for excel word generation wizard
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/profiles", requirements={"eid": "\d+"}, name="event_profiles_generator")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function exportWordGeneratorAction(Event $event, Request $request)
    {
        $configuration = new WordConfiguration($event);
        $tree          = $configuration->getConfigTreeBuilder()->buildTree();

        return $this->render(
            'event/admin/profile-generator.html.twig',
            [
                'event'              => $event,
                'config'             => $tree->getChildren()
            ]
        );
    }

    /**
     * Process transmitted configuration and provide download url
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/{type}/download/{filename}", requirements={"type": "(export|profiles)", "eid": "\d+", "filename": "([a-zA-Z0-9\s_\\.\-\(\):])+"}, name="event_export_generator_process")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportGeneratorProcessDirectAction(Event $event, string $type, Request $request)
    {
        switch ($type) {
            case 'export':
                $result = $this->generateExcelExport($request);
                break;
            case 'profiles':
                $result = $this->generateWordExport($request);
                break;
        }

        $url = $this->get('router')->generate(
            'event_export_generator_download',
            [
                'eid'      => $event->getEid(),
                'type'     => $type,
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
     * @Route("/admin/event/{eid}/{type}/download/{tmpname}/{filename}", requirements={"type": "(export|profiles)", "eid": "\d+", "tmpname": "([a-zA-Z0-9\s_\\.\-\(\):])+", "filename": "([a-zA-Z0-9\s_\\.\-\(\):])+"}, name="event_export_generator_download")
     * @Security("is_granted('participants_read', event)")
     */
    public function exportGeneratedDownloadAction(Event $event, string $type, string $tmpname, string $filename, Request $request) {
        $path = $this->getParameter('app.tmp.root.path').'/'.$tmpname;
        if (!file_exists($path)) {
            throw new NotFoundHttpException('Requested export '.$path.' not found');
        }
        $response = new BinaryFileResponse($path);

        switch ($type) {
            case 'export':
                $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            case 'profiles':
                $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                break;
        }
        if (isset($mime) && $mime !== null) {
            ResponseHelper::configureContentType(
                $response,
                $mime
            );
        }
        ResponseHelper::configureDisposition(
            $response,
            $filename
        );

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
     * Process export configuration
     *
     * @param string $type
     * @param Request $request
     * @return BinaryFileResponse
     * @deprecated
     * @Route("/admin/event/{type}/process", name="event_export_generator_process_legacy", requirements={"type": "(export|profiles)"})
     * @Security("is_granted('participants_read', event)")
     */
    public function exportGeneratorProcessAction(string $type, Request $request)
    {
        switch ($type) {
            case 'export':
                $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                $result = $this->generateExcelExport($request);
                break;
            case 'profiles':
                $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                $result = $this->generateWordExport($request);
                break;
        }

        $response = new BinaryFileResponse($result['path']);
        if (isset($mime) && $mime !== null) {
            ResponseHelper::configureContentType(
                $response,
                $mime
            );
        }
        ResponseHelper::configureDisposition(
            $response,
            $result['name']
        );

        return $response;
    }

    /**
     * Process configuration from request and provide result as array
     *
     * @param Request $request
     * @param string $configurationClassName Class to use for configuration
     * @return array
     */
    private function processRequestConfiguration(Request $request, string $configurationClassName): array
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

        $processor     = new Processor();
        $configuration = new $configurationClassName($event);
        $config        = [$configuration::ROOT_NODE_NAME => $config]; //add root config option

        $processedConfiguration = $processor->processConfiguration($configuration, $config);
        if (!$processedConfiguration['title']) {
            $processedConfiguration['title'] = 'Teilnehmer';
        }
        return $processedConfiguration;
    }

    /**
     * Provides a callable which can be used to extract textual values of participant data
     *
     * @return \Closure
     */
    public static function provideTextualValueAccessor(): callable {
        $accessor = PropertyAccess::createPropertyAccessor();

        return function (Participant $entity, string $property) use ($accessor) {
            $value = $accessor->getValue($entity, $property);
            if ($value instanceof Fillout) {
                $value = $value->getValue()->getTextualValue();
            }
            return $value;
        };
    }

    /**
     * Provide a filtered participants list for event
     *
     * @param Event $event                    Related event
     * @param string $filterConfirmed         Confirmed filter configuration
     * @param string $filterPaid              Paid filter configuration
     * @param string $filterRejectedWithdrawn Rejected filter
     * @param string|null $groupBy            Grouping
     * @param string|null $orderBy            Sorting
     * @return array
     */
    private function provideGroupedFilteredParticipantsList(
        Event $event,
        string $filterConfirmed,
        string $filterPaid,
        string $filterRejectedWithdrawn,
        ?string $groupBy = null,
        ?string $orderBy = null
    )
    {
    $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
    $paymentManager = $this->get('app.payment_manager');

    $participantList         = array_filter(
            $participationRepository->participantsList(
                $event,
                null,
                false,
                ($filterRejectedWithdrawn !== ExcelConfiguration::OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN)
            ),
            function (Participant $participant) use ($paymentManager, $filterConfirmed, $filterPaid, $filterRejectedWithdrawn) {
                $include = true;
                switch ($filterConfirmed) {
                    case ExcelConfiguration::OPTION_CONFIRMED_CONFIRMED:
                        $include = $include && $participant->isConfirmed();
                        break;
                    case ExcelConfiguration::OPTION_CONFIRMED_UNCONFIRMED:
                        $include = $include && !$participant->isConfirmed();
                        break;
                }
                switch ($filterPaid) {
                    case ExcelConfiguration::OPTION_PAID_PAID:
                        $include = $include && !$paymentManager->isParticipantRequiringPayment($participant);
                        break;
                    case ExcelConfiguration::OPTION_PAID_NOTPAID:
                        $include = $include && $paymentManager->isParticipantRequiringPayment($participant);
                        break;
                }
                switch($filterRejectedWithdrawn) {
                    case ExcelConfiguration::OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN:
                        $include = $include && !($participant->isRejected() || $participant->isWithdrawn());
                        break;
                    case ExcelConfiguration::OPTION_REJECTED_WITHDRAWN_REJECTED_WITHDRAWN:
                        $include = $include && ($participant->isRejected() || $participant->isWithdrawn());
                        break;
                }

                return $include;
            }
        );
    
        return ParticipationRepository::sortAndGroupParticipantList($participantList, $orderBy, $groupBy);
    }

    /**
     * Generate word export file and provide file info
     *
     * @param Request $request
     * @return array
     */
    private function generateWordExport(Request $request): array
    {
        $processedConfiguration = $this->processRequestConfiguration($request, WordConfiguration::class);

        $eventRepository = $this->getDoctrine()->getRepository(Event::class);
        /** @var Event $event */
        $event = $eventRepository->findOneBy(['eid' => $request->get('eid')]);

        $filterConfirmed         = ExcelConfiguration::OPTION_CONFIRMED_CONFIRMED;
        $filterPaid              = ExcelConfiguration::OPTION_PAID_ALL;
        $filterRejectedWithdrawn = ExcelConfiguration::OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN;


        $groupBy = null;
        if (isset($processedConfiguration['grouping_sorting']['grouping']['enabled']) && isset($processedConfiguration['grouping_sorting']['grouping']['field'])) {
            $groupBy = $processedConfiguration['grouping_sorting']['grouping']['field'];
        }
        $orderBy = null;
        if (isset($processedConfiguration['grouping_sorting']['sorting']['enabled']) && isset($processedConfiguration['grouping_sorting']['sorting']['field'])) {
            $orderBy = $processedConfiguration['grouping_sorting']['sorting']['field'];
        }

        $participantList = $this->provideGroupedFilteredParticipantsList(
            $event,
            $filterConfirmed,
            $filterPaid,
            $filterRejectedWithdrawn,
            $groupBy,
            $orderBy
            );

        $generator = $this->get('app.participant.profile_generator');
        $tmpPath   = $generator->generate($participantList, $processedConfiguration);

        //filter name
        $filename = $event->getTitle() . ' - ' . $processedConfiguration['title'] . '.docx';
        $filename = preg_replace('/[^\x20-\x7e]{1}/', '', $filename);

        return [
            'path' => $tmpPath,
            'name' => $filename
        ];
    }

    /**
     * Generate export file and provide file info
     *
     * @param Request $request
     * @return array
     */
    private function generateExcelExport(Request $request): array
    {
        $processedConfiguration = $this->processRequestConfiguration($request, ExcelConfiguration::class);

        $eventRepository = $this->getDoctrine()->getRepository(Event::class);
        /** @var Event $event */
        $event = $eventRepository->findOneBy(['eid' => $request->get('eid')]);

        $groupBy = null;
        if (isset($processedConfiguration['participant']['grouping_sorting']['grouping']['enabled']) && isset($processedConfiguration['participant']['grouping_sorting']['grouping']['field'])) {
            $groupBy = $processedConfiguration['participant']['grouping_sorting']['grouping']['field'];
        }
        $orderBy = null;
        if (isset($processedConfiguration['participant']['grouping_sorting']['sorting']['enabled']) && isset($processedConfiguration['participant']['grouping_sorting']['sorting']['field'])) {
            $orderBy = $processedConfiguration['participant']['grouping_sorting']['sorting']['field'];
        }

        $filterConfirmed         = $processedConfiguration['filter']['confirmed'];
        $filterPaid              = $processedConfiguration['filter']['paid'];
        $filterRejectedWithdrawn = $processedConfiguration['filter']['rejectedwithdrawn'];

        $participantList = $this->provideGroupedFilteredParticipantsList(
            $event,
            $filterConfirmed,
            $filterPaid,
            $filterRejectedWithdrawn,
            $groupBy,
            $orderBy
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
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants/profile_export.docx", requirements={"eid": "\d+"}, name="event_participants_profile")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @return Response
     * @deprecated
     */
    public function generateWordParticipantsProfileAction(Event $event) {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participants            = $participationRepository->participantsList($event);

        $config = [
            'profile' => [
                'general' =>
                    [
                        'includePrivate'     => true,
                        'includeDescription' => true,
                        'includeComments'    => true,
                        'includePrice'       => true,
                        'includeToPay'       => true,
                    ],
                'choices' =>
                    [
                        'includeShortTitle'      => true,
                        'includeManagementTitle' => true,
                        'includeNotSelected'     => false,
                    ],
            ]
        ];
        $processor     = new Processor();
        $configuration = new WordConfiguration($event);

        $processedConfiguration = $processor->processConfiguration($configuration, $config);

        $generator = $this->get('app.participant.profile_generator');
        $path      = $generator->generate($participants, $processedConfiguration);
        $response  = new BinaryFileResponse($path);
    
        ResponseHelper::configureAttachment(
            $response,
            preg_replace(
                '/[^a-zA-Z0-9\-\._ ]/', '', $event->getTitle() . ' Profile.docx'
            ),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

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
}
