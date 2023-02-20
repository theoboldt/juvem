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

use Tests\Export\TmpDirAccessingTestTrait;
use Tests\JuvemKernelTestCase;

abstract class ExportTestCase extends JuvemKernelTestCase
{
    use ParticipantTestingDataTrait, TmpDirAccessingTestTrait;

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
