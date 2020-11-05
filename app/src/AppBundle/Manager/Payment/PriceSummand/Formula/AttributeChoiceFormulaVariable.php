<?php

namespace AppBundle\Manager\Payment\PriceSummand\Formula;


use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;

class AttributeChoiceFormulaVariable implements FormulaVariableInterface
{

    /**
     * Variable name prefix
     */
    const SELECTED_PREFIX = 'choice';

    /**
     * Variable name suffix
     */
    const SELECTED_SUFFIX = 'selected';

    /**
     * choice
     *
     * @var AttributeChoiceOption
     */
    private $choice;

    /**
     * AttributeChoiceFormulaVariable constructor.
     *
     * @param AttributeChoiceOption $choice
     */
    public function __construct(AttributeChoiceOption $choice)
    {
        $this->choice = $choice;
    }

    /**
     * Get variable name for usage in formula
     *
     * @return string
     */
    public function getName(): string
    {
        return self::SELECTED_PREFIX . $this->choice->getId() . self::SELECTED_SUFFIX;
    }

    /**
     * Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->choice->getManagementTitle(true);
    }

    /**
     * Determine if variable provides numeric
     *
     * @return bool
     */
    public function isNummeric(): bool
    {
        return false;
    }

    /**
     * Determine if variable provides boolean
     *
     * @return bool
     */
    public function isBoolean(): bool
    {
        return true;
    }

    /**
     * Related choice option
     *
     * @return AttributeChoiceOption
     */
    public function getChoice(): AttributeChoiceOption
    {
        return $this->choice;
    }
}
