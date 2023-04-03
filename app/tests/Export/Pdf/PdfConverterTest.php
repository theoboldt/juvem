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


use AppBundle\InputFileNotFoundException;
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
    
    public function testConverterNotCreatedIfNotConfigured(): void
    {
        $converter = PdfConverterService::create(null, __DIR__ . '/../../../../var/tmp', null);
        $this->assertNull($converter);
    }
    
    /**
     * Extract libreoffice path
     *
     * @return string
     */
    private function provideLibreOfficePath(): string
    {
        $libreofficePath = trim(getenv('LIBREOFFICE_BINARY_PATH'));
        
        if (empty($libreofficePath)) {
            $libreofficePath = exec('which soffice', $output, $return);
            if ($return === 0) {
                $libreofficePath = getenv('LIBREOFFICE_BINARY_PATH');
            }
        }
        
        return $libreofficePath;
    }

    /**
     * Test creation of pdf from a word document
     */
    public function testConvertWordToPdf(): void
    {
        $libreofficePath = $this->provideLibreOfficePath();
        
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
        $converter = PdfConverterService::create($libreofficePath, __DIR__ . '/../../../../var/tmp', $logger);

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
    
    public function testConverterNotActuallyExisting(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configured libreoffice binary inaccessible');
        $converter = new PdfConverterService(__DIR__ . '/not_existing_file', __DIR__ . '/../../../../var/tmp', null);
        $converter->convert(__DIR__ . '/original.docx');
    }
    
    public function testConverterNotActuallyExecutable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configured libreoffice binary not executable');
        $converter = new PdfConverterService(__DIR__ . '/original.docx', __DIR__ . '/../../../../var/tmp', null);
        $converter->convert(__DIR__ . '/original.docx');
    }
    
    /**
     * @depends testConvertWordToPdf
     */
    public function testConverterInputFileUnavailable(): void
    {
        $input = __DIR__ . '/not_existing_file';
        $this->expectException(InputFileNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Input file "%s" missing', $input));

        $libreofficePath = $this->provideLibreOfficePath();

        $converter = new PdfConverterService($libreofficePath, __DIR__ . '/../../../../var/tmp', null);
        $converter->convert($input);
    }
    
    
}
