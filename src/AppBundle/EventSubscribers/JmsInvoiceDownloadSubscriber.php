<?php


namespace AppBundle\EventSubscribers;


use AppBundle\Entity\Invoice;
use AppBundle\Manager\Invoice\InvoiceManager;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
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
     * JmsInvoiceDownloadSubscriber constructor.
     *
     * @param InvoiceManager $invoiceManager
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(InvoiceManager $invoiceManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->invoiceManager = $invoiceManager;
        $this->urlGenerator   = $urlGenerator;
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
                $event->getVisitor()->addData('download_url', $url);
            }
        }
    }
}