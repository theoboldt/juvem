<?php

namespace AppBundle\Manager\Payment\PriceSummand\Formula;


use AppBundle\Entity\AcquisitionAttribute\Attribute;

class AttributeFormulaVariable implements FormulaVariableInterface
{
    
    /**
     * Attribute
     *
     * @var Attribute
     */
    private $attribute;
    
    /**
     * AttributeFormulaVariable constructor.
     *
     * @param Attribute $attribute
     */
    public function __construct(Attribute $attribute)
    {
        $this->attribute = $attribute;
    }
    
    /**
     * Get variable name for usage in formula
     *
     * @return string
     */
    public function getName(): string
    {
        return Attribute::FORMULA_VARIABLE_PREFIX . $this->attribute->getBid();
    }
    
    /**
     * Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->attribute->getManagementTitle();
    }
    
    /**
     * Determine if variable provides numeric
     *
     * @return bool
     */
    public function isNummeric(): bool
    {
        return true;
    }
    
    /**
     * Determine if variable provides boolean
     *
     * @return bool
     */
    public function isBoolean(): bool
    {
        return false;
    }
}