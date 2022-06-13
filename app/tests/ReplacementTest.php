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

use AppBundle\Anonymization\FakerReplacement;
use AppBundle\Anonymization\ReplacementArray;
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
    public function testReplacerDateNull(): void
    {
        $replacer    = new ReplacementDate(null);
        $replacement = $replacer->provideReplacement();
        $this->assertNull($replacement);
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
    public function testReplacerDateKey(): void
    {
        $replacer = new ReplacementDate('2000-01-01');
        $this->assertEquals('2000-01-01', $replacer->getKey());
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
        $this->assertMatchesRegularExpression(
            '/^2000-(?P<month>\d{2})-(?P<day>\d{2}) (?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})$/i',
            $replacement
        );
        $this->assertNotEquals('2000-01-01 10:00:00', $replacement);
    }

    /**
     * @return void
     */
    public function testReplacerDateTimeNull(): void
    {
        $replacer    = new ReplacementDateTime(null);
        $replacement = $replacer->provideReplacement();
        $this->assertNull($replacement);
    }

    /**
     * @return void
     */
    public function testReplacerDateTimeType(): void
    {
        $replacer = new ReplacementDateTime('2000-01-01 10:00:00');
        $this->assertEquals('datetime', $replacer->getType());
    }

    /**
     * @return void
     */
    public function testReplacerDateTimeKey(): void
    {
        $replacer = new ReplacementDateTime('2000-01-01 10:00:00');
        $this->assertEquals('2000-01-01 10:00:00', $replacer->getKey());
    }

    /**
     * @return void
     */
    public function testReplacerArray(): void
    {
        $replacer    = new ReplacementArray(['key_original' => 'value_original']);
        $replacement = $replacer->provideReplacement();

        $replacementDecoded = json_decode($replacement, true);
        $this->assertArrayHasKey('key_original', $replacementDecoded);
        $this->assertNotEquals('value_original', $replacementDecoded['key_original']);
    }

    /**
     * @return void
     */
    public function testReplacerArrayNumeric(): void
    {
        $replacer    = new ReplacementArray(['key_original' => 9]);
        $replacement = $replacer->provideReplacement();

        $replacementDecoded = json_decode($replacement, true);
        $this->assertArrayHasKey('key_original', $replacementDecoded);
        $this->assertNotEquals('value_original', $replacementDecoded['key_original']);
        $this->assertIsNumeric($replacementDecoded['key_original']);
    }

    /**
     * @return void
     */
    public function testReplacerArrayStringShort(): void
    {
        $replacer    = new ReplacementArray(['key_original' => 'abc']);
        $replacement = $replacer->provideReplacement();

        $replacementDecoded = json_decode($replacement, true);
        $this->assertArrayHasKey('key_original', $replacementDecoded);
        $this->assertNotEquals('value_original', $replacementDecoded['key_original']);
        $this->assertLessThanOrEqual(5, mb_strlen($replacementDecoded['key_original']));
    }

    /**
     * @return void
     */
    public function testReplacerArrayArray(): void
    {
        $replacer    = new ReplacementArray(['key_original' => ['second_key' => 'value_original']]);
        $replacement = $replacer->provideReplacement();

        $replacementDecoded = json_decode($replacement, true);
        $this->assertArrayHasKey('key_original', $replacementDecoded);
        $this->assertArrayHasKey('second_key', $replacementDecoded['key_original']);
        $this->assertNotEquals('value_original', $replacementDecoded['key_original']['second_key']);
    }

    /**
     * @return void
     */
    public function testReplacerArrayScalar(): void
    {
        $replacer    = new ReplacementArray('value_original');
        $replacement = $replacer->provideReplacement();

        $this->assertNotEquals('value_original', $replacement);
        $this->assertLessThanOrEqual(mb_strlen('value_original'), mb_strlen($replacement));
    }

    /**
     * @return void
     */
    public function testReplacerArrayScalarShort(): void
    {
        $replacer    = new ReplacementArray('val');
        $replacement = $replacer->provideReplacement();

        $this->assertNotEquals('val', $replacement);
        $this->assertLessThanOrEqual(5, mb_strlen($replacement));
    }

    /**
     * @return void
     */
    public function testReplacerArrayKey(): void
    {
        $replacer = new ReplacementArray(['key_original' => 'value_original']);
        $key      = $replacer->getKey();
        $this->assertEquals('45032e50855588f43ad3175a63b851db2a8e611f8a7f040b03c3ded8c004f119', $key);
    }

    /**
     * @return void
     */
    public function testReplacerArrayKeyScalar(): void
    {
        $replacer = new ReplacementArray('value_original');
        $key      = $replacer->getKey();
        $this->assertEquals('value_original', $key);
    }

    /**
     * @return void
     */
    public function testReplacerArrayType(): void
    {
        $replacer = new ReplacementArray(['key_original' => 'value_original']);
        $this->assertEquals('array', $replacer->getType());
    }

    public function testReplacerFaker(): void
    {
        $valueFirstName = 'Marius';
        $replacementFirstName = new FakerReplacement(
            $valueFirstName,
            'name_first',
            function (\Faker\Generator $faker) use ($valueFirstName) {
                if ($valueFirstName === null) {
                    return null;
                }
                return $faker->firstName;
            }
        );
        $replacement = $replacementFirstName->provideReplacement();
        $this->assertNotEquals($valueFirstName, $replacement);
    }
    
    public function testReplacerFakerType(): void
    {
        $valueFirstName = 'Marius';
        $replacementFirstName = new FakerReplacement(
            $valueFirstName,
            'name_first',
            function (\Faker\Generator $faker) use ($valueFirstName) {
                if ($valueFirstName === null) {
                    return null;
                }
                return $faker->firstName;
            }
        );
        $this->assertEquals('name_first', $replacementFirstName->getType());
    }
}
