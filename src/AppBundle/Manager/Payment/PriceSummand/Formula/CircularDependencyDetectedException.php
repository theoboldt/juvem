<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Payment\PriceSummand\Formula;


class CircularDependencyDetectedException extends \RuntimeException
{

    /**
     * Dependency list which can not be resolved
     *
     * @var array
     */
    private $dependants = [];

    /**
     * Create dependency message
     *
     * @param array $dependants
     * @param \Throwable|null $previous
     * @return CircularDependencyDetectedException
     */
    public static function create(array $dependants, $previous = null)
    {
        $message             = 'Circular dependency: ';
        $messageDependencies = [];

        foreach ($dependants as $source => $dependencies) {
            $messageDependencies[] = $source . ' depends on ' . implode(' and ', $dependencies);
        }

        $message .= implode(' while ', $messageDependencies);
        return new self($message, 0, $previous, $dependants);
    }

    /**
     * CircularDependencyDetectedException constructor.
     *
     * @param string     $message
     * @param int        $code
     * @param \Throwable $previous
     * @param array      $dependants
     */
    public function __construct($message, $code, $previous, array $dependants)
    {
        $this->dependants = $dependants;

        parent::__construct($message, $code, $previous);
    }


}
