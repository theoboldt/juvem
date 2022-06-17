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
     * @return float|null
     */
    public function getDisagreementFullShare(): ?float
    {
        return $this->total > 0 ? $this->disagreementFull / $this->total : null;
    }

    /**
     * @return float|null
     */
    public function getDisagreementPartialShare(): ?float
    {
        return $this->total > 0 ? $this->disagreementPartial / $this->total : null;
    }

    /**
     * @return float|null
     */
    public function getNeutralShare(): ?float
    {
        return $this->total > 0 ? $this->neutral / $this->total : null;
    }

    /**
     * @return float|null
     */
    public function getAgreementPartialShare(): ?float
    {
        return $this->total > 0 ? $this->agreementPartial / $this->total : null;
    }

    /**
     * @return float|null
     */
    public function getAgreementFullShare(): ?float
    {
        return $this->total > 0 ? $this->agreementFull / $this->total : null;
    }

    /**
     * @param int    $decimalPlaces    Specify amount of decimal places
     * @param string $decimalSeparator Decimal separator to use
     * @return string
     */
    public function getDisagreementFullPercentage(int $decimalPlaces = 0, string $decimalSeparator = ','): string
    {
        $share = $this->getDisagreementFullShare();
        return self::formatShareDecimalPlaces($share, $decimalPlaces, $decimalSeparator);
    }

    /**
     * @param int    $decimalPlaces    Specify amount of decimal places
     * @param string $decimalSeparator Decimal separator to use
     * @return string
     */
    public function getDisagreementPartialPercentage(int $decimalPlaces = 0, string $decimalSeparator = ','): string
    {
        $share = $this->getDisagreementPartialShare();
        return self::formatShareDecimalPlaces($share, $decimalPlaces, $decimalSeparator);
    }

    /**
     * @param int    $decimalPlaces    Specify amount of decimal places
     * @param string $decimalSeparator Decimal separator to use
     * @return string
     */
    public function getNeutralPercentage(int $decimalPlaces = 0, string $decimalSeparator = ','): string
    {
        $share = $this->getNeutralShare();
        return self::formatShareDecimalPlaces($share, $decimalPlaces, $decimalSeparator);
    }

    /**
     * @param int    $decimalPlaces    Specify amount of decimal places
     * @param string $decimalSeparator Decimal separator to use
     * @return string
     */
    public function getAgreementPartialPercentage(int $decimalPlaces = 0, string $decimalSeparator = ','): string
    {
        $share = $this->getAgreementPartialShare();
        return self::formatShareDecimalPlaces($share, $decimalPlaces, $decimalSeparator);
    }

    /**
     * @param int    $decimalPlaces    Specify amount of decimal places
     * @param string $decimalSeparator Decimal separator to use
     * @return string
     */
    public function getAgreementFullPercentage(int $decimalPlaces = 0, string $decimalSeparator = ','): string
    {
        $share = $this->getAgreementFullShare();
        return self::formatShareDecimalPlaces($share, $decimalPlaces, $decimalSeparator);
    }

    /**
     * Format share using decimal places
     *
     * @param float|null $share            Share
     * @param int        $decimalPlaces    Specify amount of decimal places
     * @param string     $decimalSeparator Decimal separator to use
     * @return string
     */
    public static function formatShareDecimalPlaces(
        ?float $share,
        int    $decimalPlaces,
        string $decimalSeparator = ','
    ): string {
        return $share === null ? '' : number_format($share * 100, $decimalPlaces, $decimalSeparator, '');
    }
}
