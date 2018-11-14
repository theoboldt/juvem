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


class FilloutValue
{
    /**
     * Raw value of fillout
     *
     * @var string|null
     */
    protected $rawValue;

    /**
     * Related attribute
     *
     * @var Attribute
     */
    protected $attribute;

    /**
     * FilloutValue constructor.
     *
     * @param Attribute   $attribute Related attribute
     * @param null|string $rawValue  Raw value of fillout
     */
    public function __construct(Attribute $attribute, string $rawValue = null)
    {
        $this->rawValue  = $rawValue;
        $this->attribute = $attribute;
    }

    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTextualValue();
    }

    /**
     * Get raw value
     *
     * @return null|string
     */
    public function getRawValue()
    {
        return $this->rawValue;
    }

    /**
     * Get attribute
     *
     * @return Attribute
     */
    public function getAttribute(): Attribute
    {
        return $this->attribute;
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
        return (string)$this->rawValue;
    }

    /**
     * Get value in form representation
     *
     * @return null|string
     */
    public function getFormValue()
    {
        return $this->rawValue;
    }

}
