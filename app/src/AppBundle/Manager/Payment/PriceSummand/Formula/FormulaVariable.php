<?php

namespace AppBundle\Manager\Payment\PriceSummand\Formula;


class FormulaVariable implements FormulaVariableInterface
{
    
    /**
     * Variable name
     *
     * @var string
     */
    private $name;
    
    /**
     * description
     *
     * @var string
     */
    private $description;
    
    /**
     * isNummeric
     *
     * @var bool
     */
    private $isNummeric;
    
    /**
     * isBoolean
     *
     * @var bool
     */
    private $isBoolean;
    
    /**
     * Create new nummeric variable
     *
     * @param string $name
     * @param string $description
     * @return FormulaVariable
     */
    public static function createNumeric(string $name, string $description)
    {
        return new self($name, $description, true, false);
    }
    
    /**
     * Create new boolean
     *
     * @param string $name
     * @param string $description
     * @return FormulaVariable
     */
    public static function createBoolean(string $name, string $description)
    {
        return new self($name, $description, false, true);
    }
    
    /**
     * FormulaVariable constructor.
     *
     * @param string $name
     * @param string $description
     * @param bool $isNummeric
     * @param bool $isBoolean
     */
    public function __construct(string $name, string $description, bool $isNummeric, bool $isBoolean)
    {
        $this->name        = $name;
        $this->description = $description;
        $this->isNummeric  = $isNummeric;
        $this->isBoolean   = $isBoolean;
    }
    
    
    /**
     * Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @return bool
     */
    public function isNummeric(): bool
    {
        return $this->isNummeric;
    }
    
    /**
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->isBoolean;
    }
    
}