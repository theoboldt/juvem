<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Invoice;


use AppBundle\Entity\Invoice;
use AppBundle\Form\EventMailType;
use AppBundle\Manager\AbstractMailerAwareManager;
use AppBundle\Twig\MailGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InvoiceMailer extends AbstractMailerAwareManager
{

    /**
     * @var InvoiceManager
     */
    private $invoiceManager;

    /**
     * @var InvoicePdfProvider|null
     */
    private $invoicePdfProvider = null;

    /**
     * EntityManager
     *
     * @var EntityManagerInterface
     */
    protected $em;


    /**
     * Initiate a participation manager service
     *
     * @param UrlGeneratorInterface   $urlGenerator
     * @param \Swift_Mailer           $mailer
     * @param MailGenerator           $mailGenerator
     * @param LoggerInterface|null    $logger
     * @param EntityManagerInterface  $em
     * @param InvoiceManager          $invoiceManager
     * @param InvoicePdfProvider|null $invoicePdf
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        \Swift_Mailer $mailer,
        MailGenerator $mailGenerator,
        ?LoggerInterface $logger,
        EntityManagerInterface $em,
        InvoiceManager $invoiceManager,
        ?InvoicePdfProvider $invoicePdf
    ) {
        $this->em                 = $em;
        $this->invoiceManager     = $invoiceManager;
        $this->invoicePdfProvider = $invoicePdf;
        parent::__construct($urlGenerator, $mailer, $mailGenerator, $logger);
    }

    /**
     * Mail invoices according to configuration
     *
     * @param InvoiceMailingConfiguration $configuration
     * @param array|Invoice[]             $invoices
     * @return int
     */
    public function mailInvoices(InvoiceMailingConfiguration $configuration, array $invoices): int
    {
        $sentCount = 0;
        if ($configuration->getFileType() === InvoiceMailingConfiguration::FILE_TYPE_PDF) {
            if (!$this->invoicePdfProvider) {
                throw new PdfConverterUnavailableException('PDF converter server is unavailable');
            }
            /** @var Invoice $invoice */
            foreach ($invoices as $invoice) {
                //ensure that PDFs are created first if not yet exist
                if (!$this->invoicePdfProvider->hasFile($invoice)) {
                    $this->invoicePdfProvider->createFile($invoice);
                }
            }
        }
        $eventTitle          = $configuration->getEvent()->getTitle();
        $subject             = sprintf('Rechnung für "%s"', $eventTitle);
        $contentTemplate     = str_replace(
            '{EVENT_TITLE}', $eventTitle, $configuration->getMessage()
        );
        $contentTemplate     = strip_tags($contentTemplate);
        $contentTemplateHtml = htmlentities($contentTemplate);
        $contentTemplateHtml = str_replace(["\n\n", "\r\r", "\r\n\r\n"], '</p><p>', $contentTemplateHtml);
        $contentTemplateHtml = str_replace(["\r\n"], '<br>', $contentTemplateHtml);
        $contentTemplateHtml = str_replace(["\n", "\r"], '<br>', $contentTemplateHtml);

        /** @var Invoice $invoice */
        foreach ($invoices as $invoice) {
            $participation = $invoice->getParticipation();
            if ($participation->isRejected()
                || $participation->isWithdrawn()
                || $participation->isDeleted()
                || !$participation->isConfirmed()
            ) {
                continue;
            }
            if ($configuration->getFilter() === InvoiceMailingConfiguration::SEND_NEW_FILTER && $invoice->isSent()) {
                continue;
            }
            $content = $contentTemplate;
            $content = str_replace('{PARTICIPATION_SALUTATION}', $participation->getSalutation(), $content);
            $content = str_replace('{PARTICIPATION_NAME_LAST}', $participation->getNameLast(), $content);

            $contentHtml = $contentTemplateHtml;
            $contentHtml = str_replace('{PARTICIPATION_SALUTATION}', $participation->getSalutation(), $contentHtml);
            $contentHtml = str_replace('{PARTICIPATION_NAME_LAST}', $participation->getNameLast(), $contentHtml);

            $message = $this->mailGenerator->getMessage(
                'general-raw',
                [
                    'text' => [
                        'title'   => $eventTitle,
                        'lead'    => 'Rechnung',
                        'subject' => $subject,
                        'content' => $content,

                    ],
                    'html' => [
                        'title'               => $eventTitle,
                        'lead'                => 'Rechnung',
                        'subject'             => $subject,
                        'content'             => $contentHtml,
                        'calltoactioncontent' => false,
                    ],
                ]
            );
            $message->setTo(
                $participation->getEmail(),
                $participation->fullname()
            );
            switch ($configuration->getFileType()) {
                case InvoiceMailingConfiguration::FILE_TYPE_WORD:
                    $path = $this->invoiceManager->getInvoiceFilePath($invoice);
                    $name = $invoice->getInvoiceNumber() . '.docx';
                    $type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    break;
                case InvoiceMailingConfiguration::FILE_TYPE_PDF:
                    $path = $this->invoicePdfProvider->getInvoicePdfFilePath($invoice);
                    $name = 'R' . $invoice->getInvoiceNumber() . '.pdf';
                    $type = 'application/pdf';
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown invoice file type requested');
            }
            $attachment = \Swift_Attachment::fromPath($path);
            $attachment->setFilename($name);
            $attachment->setContentType($type);
            $message->attach($attachment);

            if ($this->mailer->send($message)) {
                $invoice->setIsSent(true);
                $this->em->persist($invoice);
                $this->em->flush();
                ++$sentCount;
            }
        }

        return $sentCount;
    }

}
