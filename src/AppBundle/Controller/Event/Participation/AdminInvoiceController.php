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


use AppBundle\Entity\Event;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Participation;
use AppBundle\Manager\Invoice\PdfConverterUnavailableException;
use AppBundle\ResponseHelper;
use AppBundle\SerializeJsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\InvalidTokenHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminInvoiceController extends Controller
{
    /**
     * Page for list of invoices of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/invoices", requirements={"eid": "\d+"}, name="event_invoices_list")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsAction(Event $event)
    {
        return $this->render('event/participation/admin/invoice-list.html.twig', ['event' => $event]);
    }

    /**
     * Data provider for events invoice list grid
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/invoices.json", requirements={"eid": "\d+"}, name="event_invoices_list_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsDataAction(Event $event)
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participationEntityList = $participationRepository->participationsList($event, false, false, null);
        $invoiceManager          = $this->get('app.payment.invoice_manager');
        $invoices                = $invoiceManager->getInvoicesForEvent($event);
        $result                  = [];

        /** @var Participation $participation */
        foreach ($participationEntityList as $participation) {
            $pid = $participation->getPid();

            $rowTemplate = [
                'index'        => $pid . '-0',
                'id'           => null,
                'pid'          => $pid,
                'name_first'   => $participation->getNameFirst(),
                'name_last'    => $participation->getNameLast(),
                'sum'          => null,
                'created_at'   => null,
                'is_latest'    => 1,
                'is_available' => 0,
            ];

            $participationInvoices = [];
            /** @var Invoice $invoice */
            foreach ($invoices as $iid => $invoice) {
                if ($invoice->getParticipation()->getId() === $pid) {
                    $participationInvoices[$iid] = $invoice;
                }
            }

            if (count($participationInvoices)) {
                ksort($participationInvoices);
                /** @var Invoice $invoiceLatest */
                $invoiceLatest   = end($participationInvoices);
                $invoiceIdLatest = $invoiceLatest->getId();
                foreach ($participationInvoices as $iid => $invoice) {
                    $row                 = $rowTemplate;
                    $row['is_available'] = 1;
                    $row['index']        .= $iid;
                    $row['id']           = $iid;
                    $row['number']       = $invoice->getInvoiceNumber();
                    $row['sum']          = number_format($invoice->getSum(true), 2, ',', '.') . '&nbsp;€';
                    $row['created_at']   = $invoice->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME);

                    if ($invoiceIdLatest !== $iid) {
                        $row['is_latest'] = 0;
                    }

                    $result[] = $row;
                }
            } else {
                $result[] = $rowTemplate;
            }
        }

        return new JsonResponse($result);
    }


    /**
     * Create invoice for selected @see Participation
     *
     * @Route("/admin/event/participation/invoice/create", methods={"POST"}, name="admin_invoice_create")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @return JsonResponse
     */
    public function createInvoiceAction(Request $request)
    {
        $token = $request->get('_token');
        $pid   = $request->get('pid');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('ParticipationcreateInvoice' . $pid)) {
            throw new InvalidTokenHttpException();
        }

        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);

        $participation = $participationRepository->findDetailed($request->get('pid'));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }
        $event = $participation->getEvent();
        $this->denyAccessUnlessGranted('participants_read', $event);

        $invoiceManager = $this->get('app.payment.invoice_manager');
        $invoice        = $invoiceManager->createInvoice($participation);

        return new SerializeJsonResponse(
            [
                'success'      => true,
                'invoice'      => $invoice,
                'invoice_list' => $invoiceManager->getInvoicesForParticipation($participation),
            ]
        );
    }

    /**
     * Download created invoice
     *
     * @Route("/admin/event/{eid}/participation/invoice/{id}/{filename}", requirements={"eid": "\d+","id": "\d+",
     *                                                                    "filename": "([a-zA-Z0-9\s_\\.\-\(\):])+"},
     *                                                                    name="admin_invoice_download")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("invoice", class="AppBundle:Invoice", options={"id" = "id"})
     * @Security("is_granted('participants_read', event)")
     * @return BinaryFileResponse
     */
    public function downloadInvoiceAction(Event $event, Invoice $invoice, string $filename)
    {
        if ($invoice->getParticipation()->getEvent()->getEid() !== $event->getEid()) {
            throw new BadRequestHttpException('Incorrect invoice requested');
        }
        $invoiceManager = $this->get('app.payment.invoice_manager');

        if (!$invoiceManager->hasFile($invoice)) {
            throw new NotFoundHttpException('There is no file for transmitted invoice stored');
        }

        $response = new BinaryFileResponse($invoiceManager->getInvoiceFilePath($invoice));
        ResponseHelper::configureAttachment(
            $response,
            $filename,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        return $response;
    }
    
    /**
     * Convert created invoice to PDF and provide download
     *
     * @Route("/admin/event/{eid}/participation/invoice/{id}/pdf/{filename}", requirements={"eid": "\d+","id": "\d+", "filename": "([a-zA-Z0-9\s_\\.\-\(\):])+"}, name="admin_invoice_download_pdf")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("invoice", class="AppBundle:Invoice", options={"id" = "id"})
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @param Invoice $invoice
     * @param string $filename
     * @return BinaryFileResponse
     */
    public function downloadInvoicePdfAction(Event $event, Invoice $invoice, string $filename): Response
    {
        if ($invoice->getParticipation()->getEvent()->getEid() !== $event->getEid()) {
            throw new BadRequestHttpException('Incorrect invoice requested');
        }
        $invoiceManager = $this->get('app.payment.invoice_manager');
    
        if (!$invoiceManager->hasFile($invoice)) {
            throw new NotFoundHttpException('There is no file for transmitted invoice stored');
        }
        $pdfProvider = $this->get('app.payment.invoice_pdf_provider');
        try {
            $path = $pdfProvider->getFile($invoice);
        
        } catch (PdfConverterUnavailableException $e) {
            throw new BadRequestHttpException('PDF converter unavailable', $e);
        }
    
        $response = new BinaryFileResponse($path);
    
        ResponseHelper::configureAttachment(
            $response,
            $filename,
            'application/pdf'
        );
    
        return $response;
    }

    /**
     * Download invoice template
     *
     * @Route("/admin/event/{eid}/invoice/template.docx", requirements={"eid": "\d+"}, name="admin_invoice_template_download")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @Security("is_granted('participants_read', event)")
     * @return BinaryFileResponse
     */
    public function downloadInvoiceTemplateAction(Event $event)
    {
        if ($event->getInvoiceTemplateFile()) {
            $response = new BinaryFileResponse($event->getInvoiceTemplateFile());
        } else {
            $invoiceManager = $this->get('app.payment.invoice_manager');
            if (!file_exists($invoiceManager->getInvoiceTemplatePath())) {
                throw new NotFoundHttpException('There is no template present');
            }
            $response = new BinaryFileResponse($invoiceManager->getInvoiceTemplatePath());
        }
        ResponseHelper::configureAttachment(
            $response,
            'template.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
    
        return $response;
    }

    /**
     * Get list of @see Participation ids where new invoice should be created
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/invoice/participations", requirements={"eid": "\d+"}, methods={"POST"},
     *                                                     name="admin_invoice_recipients")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @return JsonResponse
     */
    public function getInvoiceRecipientsAction(Event $event, Request $request)
    {
        $eid = $event->getEid();
        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($request->get('_token') != $csrf->getToken('invoice-create' . $eid)) {
            throw new InvalidTokenHttpException();
        }

        $filter                  = $request->get('filter');
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participationEntityList = $participationRepository->participationsList($event, false, false, null);
        $invoiceManager          = $this->get('app.payment.invoice_manager');
        $priceManager            = $this->get('app.price_manager');
        $result                  = [];

        /** @var Participation $participation */
        foreach ($participationEntityList as $participation) {
            if ($filter) {
                $invoices = $invoiceManager->getInvoicesForParticipation($participation);
                if (count($invoices) && $filter === 'changed') {
                    $currentPrice = $priceManager->getPriceForParticipation($participation, false);
                    usort(
                        $invoices, function (Invoice $a, Invoice $b) {
                        $a = $a->getCreatedAt();
                        $b = $b->getCreatedAt();
                        if ($a === $b) {
                            return 0;
                        }
                        return ($a > $b) ? -1 : 1;
                    }
                    );
                    /** @var Invoice $invoice */
                    $invoice = reset($invoices); //most recent invoice
                    if ($invoice->getSum(false) == $currentPrice) {
                        continue;
                    }
                }
            }

            $pid      = $participation->getPid();
            $result[] = [
                'pid'   => $pid,
                'token' => $csrf->getToken('ParticipationcreateInvoice' . $pid)->getValue(),
            ];
        }

        return new JsonResponse(['participations' => $result, 'success' => true]);
    }

    /**
     * Provide invoices list filtered by event and filter
     *
     * @param Event  $event
     * @param string $filter
     * @return array
     */
    private function provideInvoices(Event $event, string $filter): array
    {
        $invoiceManager   = $this->get('app.payment.invoice_manager');
        $invoicesComplete = $invoiceManager->getInvoicesForEvent($event);

        $invoices = [];
        /** @var Invoice $invoice */
        foreach ($invoicesComplete as $invoice) {
            $pid = $invoice->getParticipation()->getPid();

            switch ($filter) {
                case 'current':
                    if (!isset($invoices[$pid])
                        || $invoices[$pid]->getCreatedAt() < $invoice->getCreatedAt()
                    ) {
                        $invoices[$pid] = $invoice;
                    }
                    break;
                case 'all':
                    $invoices[] = $invoice;
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown filter ' . $filter . ' transmitted');
            }
        }
        return $invoices;
    }

    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/invoice/{filter}_pdf.zip", requirements={"eid": "\d+", "filter":"(all|current)"},
     *                                                 name="event_invoice_download_package_pdf")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @return Response
     */
    public function downloadEventInvoicePdfPackage(Event $event, string $filter): Response
    {
        if (!$this->has('app.pdf_converter_service')) {
            throw new BadRequestHttpException('PDF converter not configured');
        }
        $pdfConverter = $this->get('app.pdf_converter_service');
        if (!$pdfConverter) {
            throw new BadRequestHttpException('PDF converter unavailable');
        }

        $invoices = $this->provideInvoices($event, $filter);
        if (!count($invoices)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $tmpPath  = $this->getParameter('app.tmp.root.path');
        $filePath = tempnam($tmpPath, 'invoice_package_');
        unlink($filePath); // need to delete file in order to prevent \ZipArchive file type error while opening
    
        $archive    = new \ZipArchive();
        $openResult = $archive->open($filePath, \ZipArchive::CREATE);
        touch($filePath); // after zip file was opened, create file again in order to keep the lock
        if ($openResult !== true) {
            throw new \InvalidArgumentException('Failed to create "' . $tmpPath . '" error code ' . $openResult);
        }

        $pdfProvider = $this->get('app.payment.invoice_pdf_provider');
    
        /** @var Invoice $invoice */
        foreach ($invoices as $invoice) {
            try {
                $convertedPath = $pdfProvider->getFile($invoice);
            } catch (PdfConverterUnavailableException $e) {
                $archive->close();
                unlink($filePath);
                throw new BadRequestHttpException('PDF converter unavailable', $e);
            }
            $archive->addFile($convertedPath, $invoice->getInvoiceNumber() . '.pdf');
        }
        $archive->close();

        $response = new BinaryFileResponse($filePath);
        $response->deleteFileAfterSend(true);
        ResponseHelper::configureAttachment(
            $response,
            $event->getTitle() . ' PDF-Rechnungen.zip',
            'application/pdf'
        );
        
        return $response;
    }
    
    /**
     * Get list of @see Participation ids where new invoice should be created
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/invoice/{filter}.zip", requirements={"eid": "\d+", "filter":"(all|current)"},
     *                                                 name="event_invoice_download_package")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @return Response
     */
    public function downloadEventInvoicePackage(Event $event, string $filter)
    {
        $invoiceManager   = $this->get('app.payment.invoice_manager');

        $invoices = $this->provideInvoices($event, $filter);
        if (!count($invoices)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }
        
        $tmpPath  = $this->getParameter('app.tmp.root.path');
        $filePath = tempnam($tmpPath, 'invoice_package_');
        unlink($filePath); // need to delete file in order to prevent \ZipArchive file type error while opening
    
        $archive    = new \ZipArchive();
        $openResult = $archive->open($filePath, \ZipArchive::CREATE);
        touch($filePath); // after zip file was opened, create file again in order to keep the lock
        if ($openResult !== true) {
            throw new \InvalidArgumentException('Failed to create "' . $tmpPath . '" error code ' . $openResult);
        }
        /** @var Invoice $invoice */
        foreach ($invoices as $invoice) {
            $archive->addFile($invoiceManager->getInvoiceFilePath($invoice), $invoice->getInvoiceNumber() . '.docx');
        }
        $archive->close();

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT, $event->getTitle() . '_Rechnungen.zip'
        );

        //ensure file deleted after request
        $this->get('event_dispatcher')->addListener(
            KernelEvents::TERMINATE,
            function (PostResponseEvent $event) use ($filePath) {
                usleep(100);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        );

        return $response;
    }
}
