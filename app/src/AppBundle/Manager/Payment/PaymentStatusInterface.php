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
 * PaymentStatusInterface
 *
 * @package AppBundle\Manager\Payment
 */
interface PaymentStatusInterface
{
    /**
     * Get price
     *
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return float|int|null
     */
    public function getPrice(bool $inEuro = false);
    
    /**
     * Get price tag
     *
     * @return EntityPriceTag
     */
    public function getPriceTag(): EntityPriceTag;
    
    /**
     * Get to pay value for this participant
     *
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|float|null
     */
    public function getToPayValue(bool $inEuro = false);
    
    /**
     * Get sum of all payments made for this participant
     *
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|float|null
     */
    public function getPaymentSum(bool $inEuro = false);
    
    /**
     * Determine if this participant is overpaid
     *
     * @return bool
     */
    public function isOverPaid(): bool;
    
    /**
     * Determine if has price set
     *
     * @return bool
     */
    public function hasPriceSet(): bool;
    
    /**
     * Determine if this has 0 as price set
     *
     * @return bool
     */
    public function isFree(): bool;
    
    /**
     * Determine if is paid
     *
     * @return bool
     */
    public function isPaid(): bool;
    
    /**
     * Determine if no payment is active because related entity is withdrawn/deleted
     *
     * @return bool
     */
    public function isInactive(): bool;
}