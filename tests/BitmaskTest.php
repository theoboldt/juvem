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
use AppBundle\BitMask\ParticipantStatus;
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
    
    public function testHasUnavailable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $f = new ParticipantFood();
        $f->has(99999);
    }
    
    public function testLabelUnavailable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $f = new ParticipantFood();
        $f->label(99999);
    }
    
    public function testToggle(): void
    {
        $f = new ParticipantFood();
        
        $before = $f->has(ParticipantFood::TYPE_FOOD_VEGAN);
        $f->toggle(ParticipantFood::TYPE_FOOD_VEGAN);
        $this->assertEquals(!$before, $f->has(ParticipantFood::TYPE_FOOD_VEGAN));
        
        $f->toggle(ParticipantFood::TYPE_FOOD_VEGAN);
        $this->assertEquals($before, $f->has(ParticipantFood::TYPE_FOOD_VEGAN));
    }
    
    public function testRawValue(): void
    {
        $f = new ParticipantFood();
        $f->enable(ParticipantFood::TYPE_FOOD_VEGAN);
        $f->enable(ParticipantFood::TYPE_FOOD_VEGETARIAN);
        
        $this->assertEquals(
            (ParticipantFood::TYPE_FOOD_VEGAN + ParticipantFood::TYPE_FOOD_VEGETARIAN),
            $f->getValue()
        );
    }
    
    public function testSetRawValue(): void
    {
        $f = new ParticipantFood();
        $f->setValue((ParticipantFood::TYPE_FOOD_VEGAN + ParticipantFood::TYPE_FOOD_VEGETARIAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGAN));
        $this->assertTrue($f->has(ParticipantFood::TYPE_FOOD_VEGETARIAN));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE));
        $this->assertFalse($f->has(ParticipantFood::TYPE_FOOD_NO_PORK));
    }
    
    public function testLabels(): void
    {
        $f = new ParticipantFood(ParticipantFood::TYPE_FOOD_VEGETARIAN);
        
        $expected = [
            ParticipantFood::TYPE_FOOD_VEGAN        => ParticipantFood::LABEL_FOOD_VEGAN,
            ParticipantFood::TYPE_FOOD_VEGETARIAN   => ParticipantFood::LABEL_FOOD_VEGETARIAN,
            ParticipantFood::TYPE_FOOD_NO_PORK      => ParticipantFood::LABEL_FOOD_NO_PORK,
            ParticipantFood::TYPE_FOOD_LACTOSE_FREE => ParticipantFood::LABEL_FOOD_LACTOSE_FREE,
        ];
        
        $this->assertEquals($expected, $f->labels());
    }
    
    public function testFormatter(): void
    {
        $formatter = ParticipantStatus::formatter();
        $status    = new ParticipantStatus(ParticipantStatus::TYPE_STATUS_CONFIRMED);
        
        $result = $formatter->formatMask($status);
        $this->assertEquals(
            '<span class="label label-success option-1">bestätigt</span>',
            $result
        );
    }
}
