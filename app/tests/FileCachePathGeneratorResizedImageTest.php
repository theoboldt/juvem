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

use AppBundle\Cache\FileCachePathGeneratorResizedImage;
use PHPUnit\Framework\TestCase;

class FileCachePathGeneratorResizedImageTest extends TestCase
{

    public function provideData(): array
    {
        return [
            [
                101,
                102,
                'mode1',
                'name1',
                '101_102_mode1/na/me1'
            ],
            [
                101,
                102,
                'mode2',
                'name3',
                '101_102_mode2/na/me3'
            ]
            
        ];
    }

    /**
     * Test path generation
     * 
     * @dataProvider provideData
     * @param int    $width
     * @param int    $height
     * @param string $mode
     * @param string $name
     * @param string $expectedPath
     */
    public function testPathGenerationTest(
        int $width,
        int $height,
        string $mode,
        string $name,
        string $expectedPath
    ): void {
        $image = new FileCachePathGeneratorResizedImage($width, $height, $mode, $name);
        var_dump($image->getPath());
        $this->assertEquals($expectedPath, $image->getPath());
        $this->assertEquals($expectedPath, (string)$image);
    }
}
