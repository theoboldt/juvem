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

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidFormula extends Constraint
{
    public $message = 'Die Formel "{{ formula }}" ist fehlerhaft: {{ error }}';

    /**
     * @return array|string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

