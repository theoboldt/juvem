<?php

namespace AppBundle\Entity;


use AppBundle\Entity\Audit\CreatedTrait;
use AppBundle\Entity\Audit\CreatorTrait;
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
class Invoice
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
     * Invoice constructor.
     *
     * @param int $id
     * @param Participation $participation
     * @param int $sum
     */
    public function __construct(int $id, Participation $participation, int $sum)
    {
        $this->id            = $id;
        $this->participation = $participation;
        $this->sum           = $sum;
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
        $year = (int)($this->createdAt ? $this->createdAt->format('y') : date('y'));
        
        return sprintf('R-%1$02d%2$03d', $year, $this->id);
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
     * Get participation
     *
     * @return Participation
     */
    public function getParticipation()
    {
        return $this->participation;
    }
}