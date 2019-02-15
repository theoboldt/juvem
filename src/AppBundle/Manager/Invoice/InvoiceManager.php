<?php

namespace AppBundle\Manager\Invoice;


use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRepository;
use AppBundle\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceManager
{
    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $em;
    
    /**
     * InvoiceManager constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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
    
    
}