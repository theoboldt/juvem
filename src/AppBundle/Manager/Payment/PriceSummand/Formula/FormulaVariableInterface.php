<?php


namespace AppBundle\Manager\Payment\PriceSummand\Formula;


interface FormulaVariableInterface
{
    
    /**
     * Get variable name for usage in formula
     *
     * @return string
     */
    public function getName(): string;
    
    /**
     * Description
     *
     * @return string
     */
    public function getDescription(): string;
    
    /**
     * Determine if variable provides numeric
     *
     * @return bool
     */
    public function isNummeric(): bool;
    
    
    /**
     * Determine if variable provides boolean
     *
     * @return bool
     */
    public function isBoolean(): bool;
    
}