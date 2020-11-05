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

use AppBundle\BitMask\ParticipantFood;
use Symfony\Component\Form\DataTransformerInterface;

class FoodTransformer implements DataTransformerInterface
{
    public function transform($originalFoodSum)
    {
        $mask = new ParticipantFood($originalFoodSum);
        return $mask->getActiveList();
    }

    public function reverseTransform($submittedFood)
    {
        return new ParticipantFood(array_sum($submittedFood));
    }
}