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

use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\FilloutValue;
use Symfony\Component\Form\DataTransformerInterface;

class AcquisitionAttributeFilloutTransformer implements DataTransformerInterface
{
    public function transform($fillout)
    {
        if ($fillout instanceof Fillout) {
            return $fillout->getValue()->getFormValue();
        }
        if ($fillout instanceof FilloutValue) {
            return $fillout->getFormValue();
        }
        return $fillout;
    }

    public function reverseTransform($value)
    {
        return $value;
    }
}
