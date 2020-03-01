<?php

namespace AppBundle\Entity;


use AppBundle\Entity\Audit\CreatedTrait;
use AppBundle\Entity\Audit\CreatorTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesCreatorInterface;
use JMS\Serializer\Annotation as Serialize;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Invoice
 *
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="participation_invoice")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InvoiceRepository")
 */
class Invoice implements ProvidesCreatedInterface, ProvidesCreatorInterface
{
    
    use CreatedTrait, CreatorTrait;
    
    /**
     * Invoice id
     *
     * @Serialize\Expose
     * @Serialize\Type("integer")
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;
    
    /**
     * participation
     *
     *
     * /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="invoices")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade")
     * @var Participation
     */
    private $participation;
    
    /**
     * Invoice sum in euro cents
     *
     * @Serialize\Expose
     * @Serialize\Type("integer")
     * @ORM\Column(type="integer", name="invoice_sum")
     *
     * @var int
     */
    private $sum;
    
    /**
     * Is set to true if this invoice was already sent via mail
     *
     * @Serialize\Expose
     * @Serialize\Type("bool")
     * @ORM\Column(name="is_sent", type="boolean", options={"unsigned":true,"default":0})
     * @var bool
     */
    protected $isSent = false;
    
    /**
     * Invoice constructor.
     *
     * @param Participation $participation
     * @param int $sum
     */
    public function __construct(Participation $participation, int $sum)
    {
        $this->participation = $participation;
        $this->sum           = $sum;
    }
    
    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Get invoice creation year
     *
     * @return int
     */
    public function getInvoiceYear(): int
    {
        return (int)($this->createdAt ? $this->createdAt->format('y') : date('y'));
    }
    
    /**
     * Get textual invoice number
     *
     * @Serialize\VirtualProperty()
     * @Serialize\SerializedName("invoice_number")
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @return string
     */
    public function getInvoiceNumber(): string
    {
        return sprintf('R-%1$02d%2$03d', $this->getInvoiceYear(), $this->id);
    }
    
    /**
     * Set participation
     *
     * @param Participation $participation
     *
     * @return Invoice
     */
    public function setParticipation(Participation $participation = null)
    {
        $this->participation = $participation;
        if (!$participation->getPhoneNumbers()->contains($this)) {
            $participation->addInvoice($this);
        }
        
        return $this;
    }
    

    /**
     * Get invoice sum
     *
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|double|null
     */
    public function getSum($inEuro = false)
    {
        if ($this->sum === null) {
            return null;
        } else {
            return $inEuro ? $this->sum / 100 : $this->sum;
        }
    }
    
    /**
     * Get participation
     *
     * @return Participation
     */
    public function getParticipation()
    {
        return $this->participation;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->isSent;
    }

    /**
     * @param bool $isSent
     */
    public function setIsSent(bool $isSent): void
    {
        $this->isSent = $isSent;
    }
}
