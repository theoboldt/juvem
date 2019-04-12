<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Twig\Extension;

use AppBundle\Manager\Payment\PaymentStatusInterface;
use Twig\Extension\AbstractExtension;

/**
 * Class PaymentInformation
 *
 * @package AppBundle\Twig\Extension
 */
class PaymentInformation extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter(
                'paymentLabel',
                [
                    $this,
                    'paymentLabel'
                ],
                [
                    'pre_escape' => 'html',
                    'is_safe'    => ['html']
                ]
            ),
            new \Twig\TwigFilter(
                'paymentInfo',
                [
                    $this,
                    'paymentInfo'
                ],
                [
                    'pre_escape' => 'html',
                    'is_safe'    => ['html']
                ]
            )
        ];
    }
    
    /**
     * Provide HTML payment label
     *
     * @param PaymentStatusInterface $paymentStatus
     * @return string Html
     */
    public function paymentLabel(PaymentStatusInterface $paymentStatus): string
    {
        return (string)self::provideLabel($paymentStatus);
    }
    
    /**
     * Provide HTML payment/price info
     *
     * @param PaymentStatusInterface $paymentStatus
     * @return string
     */
    public function paymentInfo(PaymentStatusInterface $paymentStatus): string
    {
        return (string)self::provideInfo($paymentStatus);
    }
    
    /**
     * Provide HTML payment label
     *
     * @param PaymentStatusInterface $paymentStatus
     * @return string|null
     */
    public static function provideLabel(PaymentStatusInterface $paymentStatus)
    {
        if ($paymentStatus->isOverPaid()) {
            return '<span class="label label-info option-payment option-overpaid">überbezahlt</span>';
        } elseif (!$paymentStatus->hasPriceSet()) {
            return '<span class="label label-info option-payment option-no-price">kein Preis</span>';
        } elseif ($paymentStatus->isFree()) {
            return '<span class="label label-info option-payment option-price-zero">kostenlos</span>';
        } elseif ($paymentStatus->isPaid()) {
            return '<span class="label label-info option-payment option-paid">bezahlt</span>';
        }
        
        return null;
    }
    
    /**
     * Provide HTML for payment/price
     *
     * @param PaymentStatusInterface $paymentStatus Data
     * @return string
     */
    public static function provideInfo(PaymentStatusInterface $paymentStatus)
    {
        $result = '';
        if ($paymentStatus->hasPriceSet()) {
            $result .= number_format($paymentStatus->getPrice(true), 2, ',', "'").'&nbsp;€';
            if ($paymentStatus->isOverPaid()) {
                $result .= ' (zu viel bezahlt: '
                           . number_format(($paymentStatus->getToPayValue(true) * -1), 2, ',', "'")
                           . '&nbsp;€)';
            } elseif ($paymentStatus->hasPriceSet() && $paymentStatus->isPaid()) {
                $result .= ' (bezahlt)';
            } elseif (!$paymentStatus->isPaid()) {
                $result .= ' (noch zu zahlen: '
                           . number_format($paymentStatus->getToPayValue(true), 2, ',', "'")
                           . '&nbsp;€)';
            }
            
            $result .= ' ' . (string)self::provideLabel($paymentStatus);
            
            return $result;
        } else {
            return '<i>(keiner festgelegt)</i>';
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'payment_information';
    }
}
