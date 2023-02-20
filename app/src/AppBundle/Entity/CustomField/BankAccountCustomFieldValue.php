<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\CustomField;

class BankAccountCustomFieldValue implements CustomFieldValueInterface
{
    const TYPE = 'bank_account';

    const KEY_BIC = 'bic';

    const KEY_IBAN = 'iban';

    const KEY_OWNER = 'owner';

    /**
     * BIC
     *
     * @var string|null
     */
    private ?string $bic = null;

    /**
     * IBAN
     *
     * @var string|null
     */
    private ?string $iban = null;

    /**
     * Account owner
     *
     * @var string|null
     */
    private ?string $owner = null;

    /**
     * Hydrate from array
     *
     * @param array $data JSON serialized data
     * @return BankAccountCustomFieldValue
     */
    public static function createFromArray(
        array $data
    ): BankAccountCustomFieldValue {
        return new self(
            $data[self::KEY_BIC] ?? null,
            $data[self::KEY_IBAN] ?? null,
            $data[self::KEY_OWNER] ?? null
        );
    }

    /**
     * Construct
     *
     * @param string|null $bic
     * @param string|null $iban
     * @param string|null $owner
     */
    public function __construct(
        ?string $bic = null,
        ?string $iban = null,
        ?string $owner = null
    ) {
        $this->bic   = $bic;
        $this->iban  = $iban;
        $this->owner = $owner;
    }

    /**
     * @param string|null $bic
     */
    public function setBic(?string $bic): void
    {
        $this->bic = $bic;
    }

    /**
     * @param string|null $iban
     */
    public function setIban(?string $iban): void
    {
        $this->iban = $iban;
    }

    /**
     * @param string|null $owner
     */
    public function setOwner(?string $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * Get BIC
     *
     * @return null|string
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * Get IBAN
     *
     * @param bool $wrapped If set to true iban will be provided in wrapped form
     * @return null|string Value
     */
    public function getIban(bool $wrapped = false): ?string
    {
        if ($wrapped) {
            return $this->iban === null ? null : wordwrap($this->iban, 4, ' ', true);
        } else {
            return $this->iban;

        }
    }

    /**
     * Get owner
     *
     * @return null|string
     */
    public function getOwner(): ?string
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
    public function getTextualValue(): string
    {
        if ($this->bic === null && $this->iban === null && $this->owner === null) {
            return '';
        }
        if ($this->bic && $this->getIban(true) && $this->owner) {
            return sprintf('BIC: %s, IBAN: %s, Kontoinhaber: %s', $this->bic, $this->getIban(true), $this->owner);
        } else {
            return '';
        }
    }

    /**
     * Implement \JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            self::KEY_BIC   => $this->bic,
            self::KEY_IBAN  => $this->iban,
            self::KEY_OWNER => $this->owner,
        ];
    }

    /**
     * Get type of this value
     *
     * @return string
     */
    public static function getType(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormValue()
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormValue($value): void
    {
        if (!$value instanceof self) {
            throw new \InvalidArgumentException('Object of unexpected class provided');
        }
        $this->bic   = $value->getBic();
        $this->iban  = $value->getIban();
        $this->owner = $value->getOwner();
    }
    
    /**
     * Determine if this value is equal to transmitted one
     *
     * @param CustomFieldValueInterface $other
     * @return bool
     */
    public function isEqualTo(CustomFieldValueInterface $other): bool
    {
        return (
            $other instanceof self
            && $this->getBic() === $other->getBic()
            && $this->getIban() === $other->getIban()
            && $this->getOwner() === $other->getOwner()
        );
    }
}
