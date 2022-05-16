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

use AppBundle\Anonymization\ReplacementDate;
use AppBundle\Anonymization\ReplacementDateTime;
use PHPUnit\Framework\TestCase;

class ReplacementTest extends TestCase
{

    /**
     * @return void
     */
    public function testReplacerDateInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown format "invalid" provided');
        $replacer = new ReplacementDate('invalid');
        $replacer->provideReplacement();
    }

    /**
     * @return void
     */
    public function testReplacerDate(): void
    {
        $replacer    = new ReplacementDate('2000-01-01');
        $replacement = $replacer->provideReplacement();
        $this->assertMatchesRegularExpression('/^2000-(?P<month>\d{2})-(?P<day>\d{2})$/i', $replacement);
        $this->assertNotEquals('2000-01-01', $replacement);
    }

    /**
     * @return void
     */
    public function testReplacerDateType(): void
    {
        $replacer = new ReplacementDate('2000-01-01');
        $this->assertEquals('date', $replacer->getType());
    }
    
    
    /**
     * @return void
     */
    public function testReplacerDatetimeInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown format "invalid" provided');
        $replacer = new ReplacementDateTime('invalid');
        $replacer->provideReplacement();
    }

    /**
     * @return void
     */
    public function testReplacerDateTime(): void
    {
        $replacer    = new ReplacementDateTime('2000-01-01 10:00:00');
        $replacement = $replacer->provideReplacement();
        $this->assertMatchesRegularExpression('/^2000-(?P<month>\d{2})-(?P<day>\d{2}) (?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})$/i', $replacement);
        $this->assertNotEquals('2000-01-01 10:00:00', $replacement);
    }

    /**
     * @return void
     */
    public function testReplacerDateTimeType(): void
    {
        $replacer = new ReplacementDateTime('2000-01-01 10:00:00');
        $this->assertEquals('datetime', $replacer->getType());
    }
    
    
}
