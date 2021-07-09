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


use AppBundle\Entity\Event;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

    const PLACEHOLDER_PARTICIPANT_NAME                 = 'participantName';
    const PLACEHOLDER_INVOICE_ROW_TYPE                 = 'invoiceRowType';
    const PLACEHOLDER_INVOICE_ROW_DESCRIPTION          = 'invoiceRowDescription';
    const PLACEHOLDER_INVOICE_ROW_TYPE_AND_DESCRIPTION = 'invoiceRowTypeDescription';
    const PLACEHOLDER_INVOICE_ROW_VALUE                = 'invoiceRowValue';

    const PLACEHOLDER_PID                            = 'pid';
    const PLACEHOLDER_SALUTATION_LEGACY              = 'salution'; //typo
    const PLACEHOLDER_SALUTATION                     = 'salutation';
    const PLACEHOLDER_GREETING_NAME                  = 'greetingAndName';
    const PLACEHOLDER_NAME_FIRST                     = 'nameFirst';
    const PLACEHOLDER_NAME_LAST                      = 'nameLast';
    const PLACEHOLDER_ADDRESS_STREET                 = 'addressStreet';
    const PLACEHOLDER_ADDRESS_ZIP                    = 'addressZip';
    const PLACEHOLDER_ADDRESS_CITY                   = 'addressCity';
    const PLACEHOLDER_EMAIL                          = 'email';
    const PLACEHOLDER_INVOICE_NUMBER                 = 'invoiceNumber';
    const PLACEHOLDER_INVOICE_ROW_SUM                = 'invoiceRowSum';
    const PLACEHOLDER_EVENT_TITLE                    = 'eventTitle';
    const PLACEHOLDER_EVENT_START                    = 'eventStart';
    const PLACEHOLDER_EVENT_END                      = 'eventEnd';
    const PLACEHOLDER_PARTICIPANT_NAMES_COMBINED     = 'participantNamesCombined';
    const PLACEHOLDER_INVOICE_ROW_SUM_EURO_CENTS_RAW = 'invoiceRowSumEuroCentsRaw';
    
    /**
     * InvoiceManager constructor.
     *
     * @param EntityManagerInterface $em
     * @param PaymentManager $paymentManager
     * @param string $invoiceBasePath
     * @param TokenStorageInterface|null $tokenStorage To get user if set
     */
    public function __construct(
        EntityManagerInterface $em,
        PaymentManager $paymentManager,
        string $invoiceBasePath,
        TokenStorageInterface $tokenStorage = null
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
     * @param Event $event Related event
     * @return array|Invoice[]
     */
    public function getInvoicesForEvent(Event $event): array
    {
        return $this->repository()->findByEvent($event);
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
     * @return Invoice|null
     */
    public function createInvoice(Participation $participation): ?Invoice
    {
        $event = $participation->getEvent();
        if ($event->getInvoiceTemplateFile()) {
            $templatePath = $event->getInvoiceTemplateFile()->getPathname();
        } else {
            $templatePath = $this->getInvoiceTemplatePath();
        }
        if (!file_exists($templatePath)) {
            throw new InvoiceTemplateMissingException('No invoice template available');
        }
        $templateProcessor = new TemplateProcessor($templatePath);

        $toPayValue = $this->paymentManager->getToPayValueForParticipation($participation, false);
        if ($toPayValue === null) {
            return null;
        }
        $invoice    = new Invoice($participation, $toPayValue);
        $invoice->setCreatedBy($this->user);
        $this->em->persist($invoice);
        $this->em->flush();

        $sumCents = 0;
        $elements = [];
        foreach ($participation->getParticipants() as $participant) {
            if ($participant->isWithdrawn() || $participant->isRejected() || $participant->getDeletedAt()) {
                continue; //do not take into account
            }
            $priceTag = $this->paymentManager->getEntityPriceTag($participant);
            /** @var SummandInterface $summand */
            foreach ($priceTag->getSummands() as $summand) {
                $summandValueCents = $summand->getValue(false);
                if (!$summand instanceof BasePriceSummand && $summandValueCents === 0) {
                    continue;
                }
                if ($summand instanceof BasePriceSummand) {
                    $type               = 'Grundpreis';
                    $description        = 'Grundpreis';
                    $typeAndDescription = [$type];
                } elseif ($summand instanceof FilloutSummand) {
                    if ($summandValueCents > 0) {
                        $type = 'Aufschlag';
                    } else {
                        $type = 'Rabatt';
                    }
                    $description        = ($summand instanceof AttributeAwareInterface)
                        ? $summand->getAttribute()->getFormTitle() : '';
                    $typeAndDescription = [$type, $description];
                } else {
                    $type               = 'Unbekannt';
                    $description        = 'Unbekannt';
                    $typeAndDescription = [$type];
                }

                $sumCents += $summand->getValue(false);

                $elements[] = [
                    'participant'          => $participant->fullname(),
                    'value'                => number_format($summand->getValue(true), 2, ',', '.') . ' €',
                    'type'                 => $type,
                    'description'          => $description,
                    'type_and_description' => implode(', ', $typeAndDescription),
                ];
            }
        }
        $templateProcessor->cloneRow('participantName', count($elements));
        $i = 1;
        foreach ($elements as $element) {
            $templateProcessor->setValue(self::PLACEHOLDER_PARTICIPANT_NAME . '#' . $i, $element['participant']);
            $templateProcessor->setValue(self::PLACEHOLDER_INVOICE_ROW_TYPE . '#' . $i, $element['description']);
            $templateProcessor->setValue(self::PLACEHOLDER_INVOICE_ROW_DESCRIPTION . '#' . $i, $element['type']);
            $templateProcessor->setValue(
                self::PLACEHOLDER_INVOICE_ROW_TYPE_AND_DESCRIPTION . '#' . $i, $element['type_and_description']
            );
            $templateProcessor->setValue(self::PLACEHOLDER_INVOICE_ROW_VALUE . '#' . $i, $element['value']);

            ++$i;
        }

        $eventStart = $event->getStartDate()->format(Event::DATE_FORMAT_DATE);
        if ($event->hasStartTime()) {
            $eventStart .= ' '.$event->getStartTime()->format(Event::DATE_FORMAT_TIME);
        }
        if ($event->hasEndDate()) {
            $eventEnd = $event->getEndDate()->format(Event::DATE_FORMAT_DATE);
            if ($event->hasEndTime()) {
                $eventEnd .= ' ' . $event->getEndTime()->format(Event::DATE_FORMAT_TIME);
            }
        } else {
            $eventEnd = $eventStart;
        }
        $greetingFull = sprintf(
            '%s %s %s',
            ($participation->getSalutation() === 'Frau' ? 'Sehr geehrte' : 'Sehr geehrter'),
            $participation->getSalutation(),
            $participation->getNameLast()
        );
        
        $search  = [
            self::PLACEHOLDER_PID,
            self::PLACEHOLDER_SALUTATION,
            self::PLACEHOLDER_SALUTATION_LEGACY,
            self::PLACEHOLDER_GREETING_NAME,
            self::PLACEHOLDER_NAME_FIRST,
            self::PLACEHOLDER_NAME_LAST,
            self::PLACEHOLDER_ADDRESS_STREET,
            self::PLACEHOLDER_ADDRESS_ZIP,
            self::PLACEHOLDER_ADDRESS_CITY,
            self::PLACEHOLDER_EMAIL,
            self::PLACEHOLDER_INVOICE_NUMBER,
            self::PLACEHOLDER_INVOICE_ROW_SUM,
            self::PLACEHOLDER_EVENT_TITLE,
            self::PLACEHOLDER_EVENT_START,
            self::PLACEHOLDER_EVENT_END,
            self::PLACEHOLDER_PARTICIPANT_NAMES_COMBINED,
            self::PLACEHOLDER_INVOICE_ROW_SUM_EURO_CENTS_RAW,
        ];
        $replace = [
            $participation->getPid(),
            $participation->getSalutation(),
            $participation->getSalutation(),
            $greetingFull,
            $participation->getNameFirst(),
            $participation->getNameLast(),
            $participation->getAddressStreet(),
            $participation->getAddressZip(),
            $participation->getAddressCity(),
            $participation->getEmail(),
            $invoice->getInvoiceNumber(),
            number_format($sumCents / 100, 2, ',', '.') . ' €',
            $event->getTitle(),
            $eventStart,
            $eventEnd,
            ParticipationsParticipantsNamesGrouped::combinedParticipantsNames($participation),
            $sumCents,
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
