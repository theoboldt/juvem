<?php
namespace AppBundle\Form\Transformer;

use AppBundle\Entity\AcquisitionAttributeFillout;
use Symfony\Component\Form\DataTransformerInterface;

class AcquisitionAttributeFilloutTransformer implements DataTransformerInterface
{
    public function transform($fillout)
    {
        if ($fillout instanceof AcquisitionAttributeFillout) {
            return $fillout->getValue();
        }
        return $fillout;
    }

    public function reverseTransform($value)
    {
        return $value;
    }
}