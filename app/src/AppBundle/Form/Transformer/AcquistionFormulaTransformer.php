<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

class AcquistionFormulaTransformer implements DataTransformerInterface
{
    public function transform($originalFormula)
    {
        return $originalFormula !== null ? str_replace('.', ',', $originalFormula) : null;
    }

    public function reverseTransform($submittedFormula)
    {
        return $submittedFormula !== null ? str_replace(',', '.', $submittedFormula) : null;
    }
}
