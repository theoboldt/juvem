<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\AcquisitionAttribute\Formula;


use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableInterface;

class CalculationImpossibleException extends \RuntimeException
{

    /**
     * Variable causing the error
     *
     * @var FormulaVariableInterface
     */
    private FormulaVariableInterface $variable;

    /**
     * CalculationImpossibleException constructor.
     *
     * @param FormulaVariableInterface $variable
     * @param string                   $message
     * @param int                      $code
     * @param \Throwable|null          $previous
     */
    public function __construct(
        FormulaVariableInterface $variable,
        $message = 'Variable used in formula but no value configured and no default assigned',
        $code = 0,
        \Throwable $previous = null
    ) {
        $this->variable = $variable;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param FormulaVariableInterface $variable
     * @param \Throwable|null          $previous
     * @return CalculationImpossibleException
     */
    public static function create(FormulaVariableInterface $variable, \Throwable $previous = null)
    {
        return new self(
            $variable,
            'Variable ' . $variable->getName() . ' used in formula but no value configured and no default assigned',
            0,
            $previous
        );
    }

    /**
     * @return FormulaVariableInterface
     */
    public function getVariable(): FormulaVariableInterface
    {
        return $this->variable;
    }


}
