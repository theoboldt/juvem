<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\AcquisitionAttribute\Variable;


class EventSpecificVariableDefaultValue implements EventSpecificVariableValueInterface
{
    
    /**
     * @var EventSpecificVariable
     */
    protected $variable;
    
    /**
     * EventSpecificVariableDefaultValue constructor.
     *
     * @param EventSpecificVariable $variable
     */
    public function __construct(EventSpecificVariable $variable)
    {
        $this->variable = $variable;
    }
    
    /**
     * @return null|EventSpecificVariable
     */
    public function getVariable(): ?EventSpecificVariable
    {
        return $this->variable;
    }
    
    /**
     * @return float|null
     */
    public function getValue(): ?float
    {
        return $this->variable->getDefaultValue();
    }
    
}