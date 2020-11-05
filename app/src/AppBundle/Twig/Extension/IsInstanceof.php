<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

/**
 * Twig extension to test if object is of class
 *
 * Class IsInstanceof
 *
 * @package AppBundle\Twig\Extension
 */
class IsInstanceof extends AbstractExtension
{
    /**
     * {@inheritDoc}
     */
    public function getTests()
    {
        return [
            new TwigTest('instanceof', [$this, 'isInstanceof'])
        ];
    }

    /**
     * Apply PHP @see instanceof operator to transmitted variables
     *
     * @param   mixed   $var
     * @param   mixed   $instance
     * @return bool
     */
    public function isInstanceof($var, $instance)
    {
        return $var instanceof $instance;
    }
}