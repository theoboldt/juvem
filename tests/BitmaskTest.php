<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests;


use AppBundle\BitMask\ParticipantFood;
use PHPUnit\Framework\TestCase;

class BitmaskTest extends TestCase
{

    public function testEnableSingleOption(): void
    {
        $f = new ParticipantFood();
        $f->enable(ParticipantFood::TYPE_FOOD_VEGAN);
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGAN));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_VEGETARIAN));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_NO_PORK));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE));
    }


    public function testEnableTwoOptions(): void
    {
        $f = new ParticipantFood();
        $f->enable(ParticipantFood::TYPE_FOOD_VEGAN);
        $f->enable(ParticipantFood::TYPE_FOOD_VEGETARIAN);
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGETARIAN));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_NO_PORK));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE));
    }

    public function testEnableThreeOptions(): void
    {
        $f = new ParticipantFood();
        $f->enable(ParticipantFood::TYPE_FOOD_VEGAN);
        $f->enable(ParticipantFood::TYPE_FOOD_VEGETARIAN);
        $f->enable(ParticipantFood::TYPE_FOOD_NO_PORK);
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGETARIAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_NO_PORK));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE));
    }

    public function testEnableAllOptions(): void
    {
        $f = new ParticipantFood();
        $f->enable(ParticipantFood::TYPE_FOOD_VEGAN);
        $f->enable(ParticipantFood::TYPE_FOOD_VEGETARIAN);
        $f->enable(ParticipantFood::TYPE_FOOD_NO_PORK);
        $f->enable(ParticipantFood::TYPE_FOOD_LACTOSE_FREE);
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGETARIAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_NO_PORK));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE));
    }

    public function testDisableAPreviouslyEnabledOption(): void
    {
        $f = new ParticipantFood();
        $f->enable(ParticipantFood::TYPE_FOOD_VEGAN);
        $f->enable(ParticipantFood::TYPE_FOOD_NO_PORK);
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGAN));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_VEGETARIAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_NO_PORK));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE));

        $f->disable(ParticipantFood::TYPE_FOOD_VEGAN);
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_VEGAN));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_VEGETARIAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_NO_PORK));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE));
    }

    public function testLabelUse(): void
    {
        $f = new ParticipantFood();
        $f->enable(ParticipantFood::TYPE_FOOD_VEGAN);
        $f->enable(ParticipantFood::TYPE_FOOD_NO_PORK);

        $this->assertEquals(
            [ParticipantFood::LABEL_FOOD_VEGAN, ParticipantFood::LABEL_FOOD_NO_PORK],
            $f->getActiveList(true)
        );
    }
}
