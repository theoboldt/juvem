<?php

namespace AppBundle\Manager\Invoice;


use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRepository;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Manager\Payment\PaymentManager;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class InvoiceManager
{
    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $em;
    
    /**
     * PaymentManager
     *
     * @var PaymentManager
     */
    private $paymentManager;
    
    /**
     * Path to invoice repository
     *
     * @var string
     */
    private $invoiceBasePath;
    
    /**
     * The user currently logged in
     *
     * @var User|null
     */
    protected $user = null;
    
    /**
     * InvoiceManager constructor.
     *
     * @param EntityManagerInterface $em
     * @param PaymentManager $paymentManager
     * @param string $invoiceBasePath
     * @param TokenStorage|null $tokenStorage To get user if set
     */
    public function __construct(
        EntityManagerInterface $em,
        PaymentManager $paymentManager,
        string $invoiceBasePath,
        TokenStorage $tokenStorage = null
    )
    {
        $this->em              = $em;
        $this->paymentManager  = $paymentManager;
        $this->invoiceBasePath = rtrim($invoiceBasePath, '/');
        if ($tokenStorage) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
    }
    
    /**
     * @return InvoiceRepository
     */
    public function repository()
    {
        return $this->em->getRepository(Invoice::class);
    }
    
    
    /**
     * Get list of all @see Invoice for transmitted Participation
     *
     * @param Participation $participation
     * @return Invoice[]|array
     */
    public function getInvoicesForParticipation(Participation $participation)
    {
        return $this->repository()->findByParticipation($participation);
    }
    
    /**
     * Calculate new @see Invoice
     *
     * @param Participation $participation
     * @return Invoice
     */
    public function createInvoice(Participation $participation)
    {
        $toPayValue = $this->paymentManager->getToPayValueForParticipation($participation, false);
        $invoice    = new Invoice($participation, $toPayValue);
        $invoice->setCreatedBy($this->user);
        $this->em->persist($invoice);
        $this->em->flush();
        
        $templateProcessor = new TemplateProcessor($this->getInvoiceTemplatePath());
        
        $this->ensureInvoiceDirectoryExists($invoice);
        $templateProcessor->saveAs($this->getInvoiceFilePath($invoice));
        
        return $invoice;
    }
    
    /**
     * Ensure that directory for invoice file exists
     *
     * @param Invoice $invoice Invoice
     * @return bool If created or not
     */
    private function ensureInvoiceDirectoryExists(Invoice $invoice): bool
    {
        $dir = dirname($this->getInvoiceFilePath($invoice));
        if (!file_exists($dir)) {
            $umask = umask(0);
            if (!mkdir($dir, 0770, true)) {
                throw new \RuntimeException('Failed to create ' . $dir);
            }
            umask($umask);
            return true;
        }
        return false;
    }
    
    /**
     * Determine if file exists
     *
     * @param Invoice $invoice
     * @return bool
     */
    public function hasFile(Invoice $invoice)
    {
        $path = $this->getInvoiceFilePath($invoice);
        return file_exists($path) && is_readable($path);
    }
    
    /**
     * Get path to @see Invoice file
     *
     * @param Invoice $invoice Related invoice
     * @return string
     */
    public function getInvoiceFilePath(Invoice $invoice)
    {
        return $this->invoiceBasePath . '/' . $invoice->getInvoiceYear() . '/' .
               strtolower($invoice->getInvoiceNumber()) . '.docx';
    }
    
    /**
     * Get path to template file
     *
     * @return string
     */
    private function getInvoiceTemplatePath()
    {
        return $this->invoiceBasePath . '/template.docx';
    }
    
    
}