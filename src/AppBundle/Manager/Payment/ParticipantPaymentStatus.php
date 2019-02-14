<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Payment;


use AppBundle\Entity\Participant;
use AppBundle\Manager\Payment\PriceSummand\EntityPriceTag;

/**
 * ParticipantPaymentStatus
 *
 * @package AppBundle\Manager\Payment
 */
class ParticipantPaymentStatus
{
    
    /**
     * paymentManager
     *
     * @var PaymentManager
     */
    private $paymentManager;
    
    /**
     * participant
     *
     * @var Participant
     */
    private $participant;
    
    /**
     * ParticipantPaymentStatus constructor.
     *
     * @param PaymentManager $paymentManager For lazy calculation
     * @param Participant $participant       Related participant
     */
    public function __construct(PaymentManager $paymentManager, Participant $participant)
    {
        $this->paymentManager = $paymentManager;
        $this->participant    = $participant;
    }
    
    /**
     * Get price
     *
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return float|int|null
     */
    public function getPrice(bool $inEuro = false)
    {
        return $this->paymentManager->getPriceForParticipant($this->participant, $inEuro);
    }
    
    /**
     * Get price tag
     *
     * @return EntityPriceTag
     */
    public function getPriceTag(): EntityPriceTag
    {
        return $this->paymentManager->getEntityPriceTag($this->participant);
    }
    
    /**
     * Get to pay value for this participant
     *
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|float|null
     */
    public function getToPayValue(bool $inEuro = false)
    {
        return $this->paymentManager->getToPayValueForParticipant($this->participant, $inEuro);
    }
    
    /**
     * Determine if this participant is overpaid
     *
     * @return bool
     */
    public function isOverPaid(): bool
    {
        $price          = $this->getPrice(false);
        $transactionSum = $this->paymentManager->getParticipantPaymentHistorySum($this->participant, false);
        
        return ($price + $transactionSum < 0);
    }
    
    /**
     * Determine if has price set
     *
     * @return bool
     */
    public function hasPriceSet(): bool
    {
        return $this->getPrice(false) !== null;
    }
    
    /**
     * Determine if this has 0 as price set
     *
     * @return bool
     */
    public function isFree(): bool
    {
        return $this->getPrice(false) === 0;
    }
    
    /**
     * Determine if is paid
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        $price = $this->getPrice(false);
        if ($price === null) {
            return true;
        }
        $transactionSum = $this->paymentManager->getParticipantPaymentHistorySum($this->participant, false);
        
        return ($price + $transactionSum <= 0);
        
    }
}