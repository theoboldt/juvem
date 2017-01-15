<?php
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