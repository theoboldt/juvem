<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Payment\PriceSummand;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\CustomField\CustomFieldValueInterface;

class CustomFieldValueSummand extends BaseSummand implements SummandInterface, AttributeAwareInterface
{

    /**
     * CustomField
     *
     * @var Attribute
     */
    protected Attribute $customField;

    /**
     * @var CustomFieldValueInterface
     */
    protected CustomFieldValueInterface $customFieldValue;

    /**
     * Get price in euro
     *
     * @var float|int
     */
    protected $value;

    /**
     * CustomFieldValueSummand constructor.
     *
     * @param SummandCausableInterface  $causingEntity
     * @param SummandImpactedInterface  $impactedEntity
     * @param Attribute                 $customField
     * @param CustomFieldValueInterface $customFieldValue
     * @param float|int                 $value Numeric summand value in euro
     */
    public function __construct(
        SummandCausableInterface  $causingEntity,
        SummandImpactedInterface  $impactedEntity,
        Attribute                 $customField,
        CustomFieldValueInterface $customFieldValue,
                                  $value
    ) {
        $this->customField      = $customField;
        $this->customFieldValue = $customFieldValue;
        $this->value            = $value;
        parent::__construct($impactedEntity, $causingEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'Feld';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(bool $inEuro = false)
    {
        $value = $inEuro ? $this->value : (100 * $this->value);
        if (is_float($value) && round($value, 0) === round($value, 2)) {
            return (int)round($value, 0);
        }
        return $value;
    }

    /**
     * Get related attribute
     *
     * @return Attribute
     */
    public function getAttribute(): Attribute
    {
        return $this->customField;
    }

    /**
     * @return CustomFieldValueInterface
     */
    public function getCustomFieldValue(): CustomFieldValueInterface
    {
        return $this->customFieldValue;
    }
}
