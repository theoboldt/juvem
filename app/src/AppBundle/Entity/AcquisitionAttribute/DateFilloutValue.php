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


class DateFilloutValue extends FilloutValue
{
    /**
     * FilloutValue constructor.
     *
     * @param Attribute $attribute Related attribute
     * @param null|\DateTime|string $rawValue Raw value of fillout
     */
    public function __construct(Attribute $attribute, $rawValue = null)
    {
        if ($rawValue !== null & !$rawValue instanceof \DateTimeInterface) {
            try {
                $rawValue = new \DateTime($rawValue);
                $rawValue->setTime(10, 0, 0);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to convert "'.$rawValue.'" to datetime', 0, $e);
            }
        }
        
        //intentionally not calling parent
        $this->attribute = $attribute;
        $this->rawValue  = $rawValue;
    }
    
    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @param string $choicePresentation Configuration for selected choice option presentation, @see
     *                                   AttributeChoiceOption
     * @return string
     */
    public function getTextualValue(string $choicePresentation = AttributeChoiceOption::PRESENTATION_FORM_TITLE)
    {
        if ($this->rawValue instanceof \DateTimeInterface) {
            return $this->rawValue->format('d.m.Y');
        }
        return '';
    }
}
