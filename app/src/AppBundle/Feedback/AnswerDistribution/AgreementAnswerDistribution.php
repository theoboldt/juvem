<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Feedback\AnswerDistribution;

class AgreementAnswerDistribution
{

    private int $disagreementFull;

    private int $disagreementPartial;

    private int $neutral;

    private int $agreementPartial;

    private int $agreementFull;

    private int $total;

    /**
     * @param int $disagreementFull
     * @param int $disagreementPartial
     * @param int $neutral
     * @param int $agreementPartial
     * @param int $agreementFull
     */
    public function __construct(
        int $disagreementFull,
        int $disagreementPartial,
        int $neutral,
        int $agreementPartial,
        int $agreementFull
    ) {
        $this->disagreementFull    = $disagreementFull;
        $this->disagreementPartial = $disagreementPartial;
        $this->neutral             = $neutral;
        $this->agreementPartial    = $agreementPartial;
        $this->agreementFull       = $agreementFull;
        $this->total               = $disagreementFull
                                     + $disagreementPartial
                                     + $neutral
                                     + $agreementPartial
                                     + $agreementFull;
    }

    /**
     * @return int
     */
    public function getDisagreementFull(): int
    {
        return $this->disagreementFull;
    }

    /**
     * @return int
     */
    public function getDisagreementPartial(): int
    {
        return $this->disagreementPartial;
    }

    /**
     * @return int
     */
    public function getNeutral(): int
    {
        return $this->neutral;
    }

    /**
     * @return int
     */
    public function getAgreementPartial(): int
    {
        return $this->agreementPartial;
    }

    /**
     * @return int
     */
    public function getAgreementFull(): int
    {
        return $this->agreementFull;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return float
     */
    public function getDisagreementFullShare(): float
    {
        return $this->disagreementFull / $this->total;
    }

    /**
     * @return float
     */
    public function getDisagreementPartialShare(): float
    {
        return $this->disagreementPartial / $this->total;
    }

    /**
     * @return float
     */
    public function getNeutralShare(): float
    {
        return $this->neutral / $this->total;
    }

    /**
     * @return float
     */
    public function getAgreementPartialShare(): float
    {
        return $this->agreementPartial / $this->total;
    }

    /**
     * @return float
     */
    public function getAgreementFullShare(): float
    {
        return $this->agreementFull / $this->total;
    }

    /**
     * @param int $decimalPlaces Specify amount of decimal places
     * @return string
     */
    public function getDisagreementFullPercentage(int $decimalPlaces = 0): string
    {
        return number_format($this->getDisagreementFullShare() * 100, $decimalPlaces, ',', '');
    }

    /**
     * @param int $decimalPlaces Specify amount of decimal places
     * @return string
     */
    public function getDisagreementPartialPercentage(int $decimalPlaces = 0): string
    {
        return number_format($this->getDisagreementPartialShare() * 100, $decimalPlaces, ',', '');
    }

    /**
     * @param int $decimalPlaces Specify amount of decimal places
     * @return string
     */
    public function getNeutralPercentage(int $decimalPlaces = 0): string
    {
        return number_format($this->getNeutralShare() * 100, $decimalPlaces, ',', '');
    }

    /**
     * @param int $decimalPlaces Specify amount of decimal places
     * @return string
     */
    public function getAgreementPartialPercentage(int $decimalPlaces = 0): string
    {
        return number_format($this->getAgreementPartialShare() * 100, $decimalPlaces, ',', '');
    }

    /**
     * @param int $decimalPlaces Specify amount of decimal places
     * @return string
     */
    public function getAgreementFullPercentage(int $decimalPlaces = 0): string
    {
        return number_format($this->getAgreementFullShare() * 100, $decimalPlaces, ',', '');
    }

}
