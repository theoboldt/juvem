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


class ChoiceFilloutValue extends FilloutValue
{
    /**
     * Selected choices
     *
     * @var array|int[]|int
     */
    protected $value;

    /**
     * FilloutValue constructor.
     *
     * @param Attribute   $attribute Related attribute
     * @param null|string $rawValue  Raw value of fillout
     */
    public function __construct(Attribute $attribute, string $rawValue = null)
    {
        if ($attribute->isMultipleChoiceType()) {
            $this->value = [];
            if ($rawValue) {
                $this->value = json_decode($rawValue, true);
            }
        } else {
            $this->value = $rawValue;
        }

        parent::__construct($attribute, $rawValue);
    }

    /**
     * Get list of @see AttributeChoiceOption which are selected
     *
     * @return array|AttributeChoiceOption[]
     */
    public function getSelectedChoices(): array
    {
        $choicesAvailable = $this->attribute->getChoiceOptions();
        $selected         = [];
        if (!is_array($this->value)) {
            $this->value = [$this->value];
        }

        /** @var AttributeChoiceOption $choice */
        foreach ($choicesAvailable as $choice) {
            if (in_array($choice->getId(), $this->value)) {
                $selected[$choice->getId()] = $choice;
            }
        }
        return $selected;
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
        if ($this->value === null) {
            return '';
        }
        $choices = $this->getSelectedChoices();
        $values  = [];
        foreach ($choices as $choice) {
            switch ($choicePresentation) {
                case AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE:
                    $values[] = $choice->getManagementTitle(true);
                    break;
                case AttributeChoiceOption::PRESENTATION_SHORT_TITLE:
                    $values[] = $choice->getShortTitle(true);
                    break;
                default:
                case AttributeChoiceOption::PRESENTATION_FORM_TITLE:
                    $values[] = $choice->getFormTitle();
                    break;
            }
        }
        return implode(', ', $values);
    }

    /**
     * Get value in form representation
     *
     * @return null|string
     */
    public function getFormValue()
    {
        if (!$this->attribute->isMultipleChoiceType() && is_array($this->value)) {
            //this is a single selection, but multi selection is stored
            return reset($this->value);

        }
        return $this->value;
    }

}
