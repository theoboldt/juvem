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
use Smalot\PdfParser\Parser;
use Tests\Export\TmpDirAccessingTestTrait;

class PdfConverterTest extends TestCase
{

    use TmpDirAccessingTestTrait;

    /**
     * Pdf file
     *
     * @var string
     */
    private static ?string $pdfFile;

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
        $libreofficePath = trim(getenv('LIBREOFFICE_BINARY_PATH'));

        if (empty($libreofficePath)) {
            $libreofficePath = exec('which soffice', $output, $return);
            if ($return === 0) {
                $libreofficePath = getenv('LIBREOFFICE_BINARY_PATH');
            }
        }

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

        self::$pdfFile = $converter->convert(__DIR__ . '/original.docx');
        self::$files[] = self::$pdfFile;

        $this->assertFileExists(self::$pdfFile);
        $this->assertGreaterThan(7500, filesize(self::$pdfFile));
    }

    /**
     * @depends testConvertWordToPdf
     */
    public function testTextContentOfPdfFile(): void
    {
        if (!self::$pdfFile) {
            $this->markTestSkipped('Requiring pdf file for text test');
        }
        
        $parser = new Parser();
        $pdf    = $parser->parseFile(self::$pdfFile);

        $text = $pdf->getText();
        $this->assertEquals('Juvem Test for Word to Pdf converter', $text);
    }
}
