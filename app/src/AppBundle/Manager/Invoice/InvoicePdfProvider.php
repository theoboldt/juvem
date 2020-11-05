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
use AppBundle\PdfConverterService;

class InvoicePdfProvider
{
    /**
     * PaymentManager
     *
     * @var InvoiceManager
     */
    private $invoiceManager;
    
    /**
     * Path to invoice repository
     *
     * @var string
     */
    private $invoiceBasePath;
    
    /**
     * Converter
     *
     * @var PdfConverterService|null
     */
    private $converter;
    
    /**
     * InvoicePdfProvider constructor.
     *
     * @param InvoiceManager $invoiceManager
     * @param string $invoiceBasePath
     * @param PdfConverterService|null $converter
     */
    public function __construct(
        InvoiceManager $invoiceManager,
        string $invoiceBasePath,
        ?PdfConverterService $converter = null
    )
    {
        $this->invoiceManager  = $invoiceManager;
        $this->invoiceBasePath = $invoiceBasePath;
        $this->converter       = $converter;
    }
    
    /**
     * Create a new invoice pdf file
     *
     * @param Invoice $invoice Input Invoice
     * @return string
     */
    public function createFile(Invoice $invoice): string
    {
        $inputPath = $this->invoiceManager->getInvoiceFilePath($invoice);
        if (!$this->converter) {
            throw new PdfConverterUnavailableException('Pdf converter is not configured');
        }
        $outputPath = $this->converter->convert($inputPath);
        $resultPath = $this->getInvoicePdfFilePath($invoice);
        if (!rename($outputPath, $resultPath)) {
            throw new UnableToCreateInvoicePdfException('Failed to move file');
        }
        return $resultPath;
    }
    
    /**
     * Get the invoice PDF file, try to create it if it does not yet exist
     *
     * @param Invoice $invoice
     * @return string PDF file path
     */
    public function getFile(Invoice $invoice): string
    {
        if ($this->hasFile($invoice)) {
            return $this->getInvoicePdfFilePath($invoice);
        } else {
            return $this->createFile($invoice);
        }
    }
    
    /**
     * Determine if file exists
     *
     * @param Invoice $invoice
     * @return bool
     */
    public function hasFile(Invoice $invoice)
    {
        $path = $this->getInvoicePdfFilePath($invoice);
        return file_exists($path) && is_readable($path);
    }
    
    /**
     * Get path to @param Invoice $invoice Related invoice
     *
     * @return string
     * @see Invoice file
     *
     */
    public function getInvoicePdfFilePath(Invoice $invoice)
    {
        return sprintf(
            '%s/%d/%d/%s.pdf',
            $this->invoiceBasePath,
            $invoice->getInvoiceYear(),
            $invoice->getParticipation()->getPid(),
            strtolower($invoice->getInvoiceNumber())
        );
    }
}