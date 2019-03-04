<?php

namespace AppBundle\Manager\Invoice;


use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRepository;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Manager\Payment\PaymentManager;
use AppBundle\Manager\Payment\PriceSummand\AttributeAwareInterface;
use AppBundle\Manager\Payment\PriceSummand\BasePriceSummand;
use AppBundle\Manager\Payment\PriceSummand\FilloutSummand;
use AppBundle\Manager\Payment\PriceSummand\SummandInterface;
use AppBundle\Twig\Extension\ParticipationsParticipantsNamesGrouped;
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
        $templatePath = $this->getInvoiceTemplatePath();
        if (!file_exists($templatePath)) {
            throw new \RuntimeException('No invoice template available');
        }
        
        $toPayValue = $this->paymentManager->getToPayValueForParticipation($participation, false);
        $invoice    = new Invoice($participation, $toPayValue);
        $invoice->setCreatedBy($this->user);
        $this->em->persist($invoice);
        $this->em->flush();
        
        $templateProcessor = new TemplateProcessor($templatePath);
        
        $sumCents = 0;
        $elements = [];
        foreach ($participation->getParticipants() as $participant) {
            $priceTag = $this->paymentManager->getEntityPriceTag($participant);
            /** @var SummandInterface $summand */
            foreach ($priceTag->getSummands() as $summand) {
                
                if ($summand instanceof BasePriceSummand) {
                    $type        = 'Grundpreis';
                    $description = '';
                } elseif ($summand instanceof FilloutSummand) {
                    $type        = 'Aufschlag/Rabatt';
                    $description = ($summand instanceof AttributeAwareInterface)
                        ? $summand->getAttribute()->getFormTitle() : '';
                } else {
                    $type        = 'Unbekannt';
                    $description = 'Unbekannt';
                }
                
                $sumCents += $summand->getValue(false);
                
                $elements[] = [
                    'participant' => $participant->fullname(),
                    'value'       => number_format($summand->getValue(true), 2, ',', '.') . ' €',
                    'type'        => $type,
                    'description' => $description,
                ];
            }
        }
        $templateProcessor->cloneRow('participantName', count($elements));
        $i = 1;
        foreach ($elements as $element) {
            $templateProcessor->setValue('participantName#' . $i, $element['participant']);
            $templateProcessor->setValue('invoiceRowDescription#' . $i, $element['description']);
            $templateProcessor->setValue('invoiceRowType#' . $i, $element['type']);
            $templateProcessor->setValue('invoiceRowValue#' . $i, $element['value']);
            
            ++$i;
        }
        
        $search  = [
            'salution',
            'nameFirst',
            'nameLast',
            'addressStreet',
            'addressZip',
            'addressCity',
            'email',
            'invoiceNumber',
            'invoiceRowSum',
            'eventTitle',
            'participantNamesCombined',
        ];
        $replace = [
            $participation->getSalutation(),
            $participation->getNameFirst(),
            $participation->getNameLast(),
            $participation->getAddressStreet(),
            $participation->getAddressZip(),
            $participation->getAddressCity(),
            $participation->getEmail(),
            $invoice->getInvoiceNumber(),
            number_format($sumCents / 100, 2, ',', '.') . ' €',
            $participation->getEvent()->getTitle(),
            ParticipationsParticipantsNamesGrouped::combinedParticipantsNames($participation),
        ];
        $templateProcessor->setValue($search, $replace);
        
        
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
        return sprintf(
            '%s/%d/%d/%s.docx',
            $this->invoiceBasePath,
            $invoice->getInvoiceYear(),
            $invoice->getParticipation()->getPid(),
            strtolower($invoice->getInvoiceNumber())
        );
    }
    
    /**
     * Get path to template file
     *
     * @return string
     */
    public function getInvoiceTemplatePath()
    {
        return $this->invoiceBasePath . '/template.docx';
    }
    
    
}