<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Export\Excel;

use PHPUnit\Framework\TestCase;

abstract class ExportTestCase extends TestCase
{
    use ParticipantTestingDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::ensureTmpDirAccessible();
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTmpFiles();
        parent::tearDownAfterClass();
    }

}
