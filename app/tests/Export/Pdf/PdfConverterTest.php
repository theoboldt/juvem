<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Export\Pdf;


use AppBundle\PdfConverterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Tests\Export\TmpDirAccessingTestTrait;

class PdfConverterTest extends TestCase
{

    use TmpDirAccessingTestTrait;


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

    /**
     * Test creation of pdf from a word document
     */
    public function testConvertWordToPdf(): void
    {
        $libreofficePath = getenv('LIBREOFFICE_BINARY_PATH');

        if (empty($libreofficePath)) {
            $this->markTestSkipped('Libreoffice binary must be available and configured for converter tests');
            return;
        }
        if (!file_exists($libreofficePath)) {
            $this->addWarning('Configured libreoffice binary inaccessible');
        }
        if (!is_executable($libreofficePath)) {
            $this->addWarning('Configured libreoffice binary not executable');
        }

        $logger    = new TestLogger();
        $converter = new PdfConverterService($libreofficePath, __DIR__ . '/../../../var/tmp', $logger);
        
        $result = $converter->convert(__DIR__.'/original.docx');
        self::$files[] = $result;

        $this->assertFileExists($result);
        $this->assertGreaterThan(7500, filesize($result));
    }
}
