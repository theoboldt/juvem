<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception is thrown if xsrf token is invalid
 */
class InvalidTokenHttpException extends HttpException
{
    /**
     * Constructor.
     *
     * @param string     $message  The internal exception message
     * @param \Exception $previous The previous exception
     * @param int        $code     The internal exception code
     */
    public function __construct($message = 'In der Zwischenzeit haben sich Teile dieser Seite geändert. Sie müssen sie aktualisieren, um fortfahren zu können.', \Exception $previous = null, $code = 0)
    {
        parent::__construct(408, $message, $previous, array(), $code);
    }

}