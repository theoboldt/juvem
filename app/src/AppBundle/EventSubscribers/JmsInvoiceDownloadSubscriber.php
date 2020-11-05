<?php


namespace AppBundle\EventSubscribers;


use AppBundle\Entity\Invoice;
use AppBundle\Manager\Invoice\InvoiceManager;
use AppBundle\PdfConverterService;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JmsInvoiceDownloadSubscriber implements EventSubscriberInterface
{
    /**
     * invoiceManager
     *
     * @var InvoiceManager
     */
    private $invoiceManager;
    
    /**
     * Router used to create the routes for the transmitted pages
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    
    /**
     * PDF converter
     *
     * @var PdfConverterService
     */
    private $pdfConverter;
    
    /**
     * JmsInvoiceDownloadSubscriber constructor.
     *
     * @param InvoiceManager $invoiceManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param PdfConverterService|null $pdfConverter
     */
    public function __construct(
        InvoiceManager $invoiceManager, UrlGeneratorInterface $urlGenerator, ?PdfConverterService $pdfConverter = null
    )
    {
        $this->invoiceManager = $invoiceManager;
        $this->urlGenerator   = $urlGenerator;
        $this->pdfConverter   = $pdfConverter;
    }
    
    /**
     * @inheritdoc
     */
    static public function getSubscribedEvents()
    {
        return [
            [
                'event'  => 'serializer.post_serialize', 'class' => Invoice::class,
                'method' => 'onPostSerialize'
            ],
        ];
    }
    
    public function onPostSerialize(ObjectEvent $event)
    {
        $invoice = $event->getObject();
        if ($invoice instanceof Invoice) {
            if ($this->invoiceManager->hasFile($invoice)) {
                $url = $this->urlGenerator->generate(
                    'admin_invoice_download',
                    [
                        'eid'      => $invoice->getParticipation()->getEvent()->getEid(),
                        'id'       => $invoice->getId(),
                        'filename' => $invoice->getInvoiceNumber() . '.docx'
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                
                $visitor = $event->getVisitor();
                if ($visitor instanceof JsonSerializationVisitor) {
                    $visitor->visitProperty(new StaticPropertyMetadata('', 'download_url', null), $url);
                    if ($this->pdfConverter) {
                        $urlPdf = $this->urlGenerator->generate(
                            'admin_invoice_download_pdf',
                            [
                                'eid'      => $invoice->getParticipation()->getEvent()->getEid(),
                                'id'       => $invoice->getId(),
                                'filename' => $invoice->getInvoiceNumber() . '.pdf'
                            ],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        );
                        $visitor->visitProperty(new StaticPropertyMetadata('', 'download_url_pdf', null), $urlPdf);
                    }
                }
            }
        }
    }
}