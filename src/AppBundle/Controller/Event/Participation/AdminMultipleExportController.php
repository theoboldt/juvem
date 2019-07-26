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
use AppBundle\Export\Customized\Configuration;
use AppBundle\Export\Customized\CustomizedExport;
use AppBundle\Export\ParticipantsBirthdayAddressExport;
use AppBundle\Export\ParticipantsExport;
use AppBundle\Export\ParticipantsMailExport;
use AppBundle\Export\ParticipationsExport;
use AppBundle\InvalidTokenHttpException;
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
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Teilnehmer.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

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
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Anmeldungen.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

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
    public function createExcelTemplateConfigurationAction(Event $event, Request $request)
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
    public function exportExcelGeneratorProcessDirectAction(Event $event, Request $request)
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
    public function exportExcelGeneratedDownloadAction(Event $event, string $tmpname, string $filename, Request $request) {
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
    public function exportExcelGeneratorProcessAction(Event $event, Request $request)
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
        
        $groupBy = null;
        if (isset($processedConfiguration['participant']['grouping_sorting']['grouping']['enabled']) && isset($processedConfiguration['participant']['grouping_sorting']['grouping']['field'])) {
            $groupBy = $processedConfiguration['participant']['grouping_sorting']['grouping']['field'];
        }
        $orderBy = null;
        if (isset($processedConfiguration['participant']['grouping_sorting']['sorting']['enabled']) && isset($processedConfiguration['participant']['grouping_sorting']['sorting']['field'])) {
            $orderBy = $processedConfiguration['participant']['grouping_sorting']['sorting']['field'];
        }
    
        $extractTextualValue = self::provideTextualValueAccessor();
        $compareValues       = function (Participant $a, Participant $b, string $property) use ($extractTextualValue) {
            $aValue = $extractTextualValue($a, $property);
            $bValue = $extractTextualValue($b, $property);
        
            if ($aValue == $bValue) {
                return 0;
            }
            return ($aValue < $bValue) ? -1 : 1;
        };
    
        if ($groupBy || $orderBy) {
            uasort(
                $participantList,
                function (Participant $a, Participant $b) use ($groupBy, $orderBy, $compareValues) {
                    $result = 0;
                    if ($groupBy) {
                        $result = $compareValues($a, $b, $groupBy);
                    }
                    if ($orderBy && $result === 0) {
                        $result = $compareValues($a, $b, $orderBy);
                    }
                
                    return $result;
                }
            );
        }

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
        $configuration = new \AppBundle\Manager\ParticipantProfile\Configuration();

        $processedConfiguration = $processor->processConfiguration($configuration, $config);

        $generator = $this->get('app.participant.profile_generator');
        $path      = $generator->generate($participants, $processedConfiguration);
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
}
