<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\AcquisitionAttribute;


class BankAccountFilloutValue extends FilloutValue
{

    /**
     * BIC
     *
     * @var string|null
     */
    private $bic = null;

    /**
     * IBAN
     *
     * @var string|null
     */
    private $iban = null;

    /**
     * Account owner
     *
     * @var string|null
     */
    private $owner = null;

    /**
     * FilloutValue constructor.
     *
     * @param Attribute   $attribute Related attribute
     * @param null|string $rawValue  Raw value of fillout
     */
    public function __construct(Attribute $attribute, string $rawValue = null)
    {
        if ($rawValue) {
            $value = json_decode($rawValue, true);

            if (isset($value['bankAccountBic'])) {
                $this->bic = str_replace(' ', '', $value['bankAccountBic']);
            }
            if (isset($value['bankAccountIban'])) {
                $this->iban = str_replace(' ', '', $value['bankAccountIban']);
            }
            if (isset($value['bankAccountOwner'])) {
                $this->owner = $value['bankAccountOwner'];
            }

        }
        parent::__construct($attribute, $rawValue);
    }

    /**
     * @return null|string
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Get IBAN
     *
     * @param bool $wrapped If set to true iban will be provided in wrapped form
     * @return null|string Value
     */
    public function getIban($wrapped = false)
    {
        if ($wrapped) {
            return $this->iban === null ? null : wordwrap($this->iban, 4, ' ', true);
        } else {
            return $this->iban;

        }
    }

    /**
     * @return null|string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @return string
     */
    public function getTextualValue()
    {
        if ($this->rawValue === null) {
            return '';
        }

        return sprintf('BIC: %s, IBAN: %s, Kontoinhaber: %s', $this->bic, $this->getIban(true), $this->owner);
    }

    /**
     * Get json decoded value as array
     *
     * @return array
     */
    public function getFormValue(): array
    {
        return [
            'bankAccountBic'   => $this->bic,
            'bankAccountIban'  => $this->iban,
            'bankAccountOwner' => $this->owner,
        ];
    }
}
