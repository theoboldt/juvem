<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\JuvemKernelTestCase;


/**
 * Class DataExportCommandTest
 *
 * @requires function zip_open
 */
class DataExportCommandTest extends JuvemKernelTestCase
{
    /**
     * @var string
     */
    private static string $tmpFilePath;

    /**
     * @var array|string[]
     */
    private static array $files = [];

    public static function tearDownAfterClass(): void
    {
        foreach (self::$files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        self::$files = [];
        parent::tearDownAfterClass();
    }

    public function testExecuteDataExportCommand(): void
    {
        if (!`which mysqldump`) {
            $this->markTestSkipped('mysqldump program unavailable');
        }

        $tmpDir = __DIR__ . '/../../../var/tmp';
        if (!file_exists($tmpDir)) {
            $umask = umask();
            umask(0);
            if (!mkdir($tmpDir, 0777, true)) {
                umask($umask);
                throw new \RuntimeException('Precondition failed: Tmp dir inaccessible');
            }
            umask($umask);
        }
        self::$tmpFilePath = $tmpDir . '/' . uniqid('test_export');
        self::$files[]     = self::$tmpFilePath;

        $kernel      = static::createKernel();
        $application = new Application($kernel);

        $command       = $application->find('app:data:export');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--quiet'          => true,
                '--no-interaction' => true,
                'path'             => self::$tmpFilePath,
            ]
        );
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Moving to target... done', $output);
        $this->assertFileExists(self::$tmpFilePath);
    }

    /**
     * @depends testExecuteDataExportCommand
     */
    public function testExportFileExists(): void
    {
        $this->assertFileExists(self::$tmpFilePath);
    }

    /**
     * @depends testExportFileExists
     */
    public function testDatabaseBackupAvailable(): void
    {
        $files = $this->getBackupFiles();
        $this->assertContains('/database.sql', $files);
        $zip = $this->openArchive();
        $zip->extractTo(__DIR__ . '/../../../var/tmp', ['/database.sql']);
        $filesize = filesize(__DIR__ . '/../../../var/tmp/database.sql');
        $zip->close();
        unlink(__DIR__ . '/../../../var/tmp/database.sql');
        $this->assertGreaterThan(1024, $filesize);
    }

    /**
     * Open archive
     *
     * @return \ZipArchive
     */
    private function openArchive(): \ZipArchive
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open(self::$tmpFilePath), 'Failed to open zip');
        return $zip;
    }

    /**
     * ZIP files
     *
     * @return array|string[]
     */
    private function getBackupFiles(): array
    {
        $zip   = $this->openArchive();
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[] = $zip->getNameIndex($i);
        }
        $zip->close();
        return $files;
    }
}
