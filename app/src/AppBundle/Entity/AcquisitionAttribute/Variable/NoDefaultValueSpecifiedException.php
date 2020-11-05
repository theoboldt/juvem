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


class NoDefaultValueSpecifiedException extends \RuntimeException
{
    /**
     * @var EventSpecificVariable
     */
    protected $variable;
    
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     *
     * @link  https://php.net/manual/en/exception.construct.php
     * @param EventSpecificVariable $variable
     * @param string $message      [optional] The Exception message to throw.
     * @param int $code            [optional] The Exception code.
     * @param \Throwable $previous [optional] The previous throwable used for the exception chaining.
     * @since 5.1
     */
    public function __construct(EventSpecificVariable $variable, $message = "", $code = 0, \Throwable $previous = null)
    {
        $this->variable = $variable;
        parent::__construct($message, $code, $previous);
    }
}