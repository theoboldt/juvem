<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\BitMask;

class ParticipantFood extends BitMaskAbstract
{
    const TYPE_FOOD_VEGAN        = 1;
    const TYPE_FOOD_VEGETARIAN   = 2;
    const TYPE_FOOD_NO_PORK      = 4;
    const TYPE_FOOD_LACTOSE_FREE = 8;

    const LABEL_FOOD_VEGAN        = 'vegan';
    const LABEL_FOOD_VEGETARIAN   = 'vegetarisch';
    const LABEL_FOOD_NO_PORK      = 'ohne Schweinefleisch';
    const LABEL_FOOD_LACTOSE_FREE = 'laktosefrei';
}