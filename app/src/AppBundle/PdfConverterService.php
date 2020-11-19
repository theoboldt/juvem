<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle;


use Psr\Log\LoggerInterface;

class PdfConverterService
{
    
    /**
     * Path to libreoffice
     *
     * @var string
     */
    private $libreofficePath;
    
    /**
     * Path to temporary dir
     *
     * @var string
     */
    protected $tmpPath;
    
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * PdfConverterService constructor.
     *
     * @param string|null $libreofficePath Path to libreoffice binary
     * @param string $tmpPath
     * @param LoggerInterface|null $logger Logger
     */
    public function __construct(string $libreofficePath, string $tmpPath, ?LoggerInterface $logger = null)
    {
        $this->libreofficePath = $libreofficePath;
        $this->tmpPath         = rtrim($tmpPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'pdf';
        $this->logger          = $logger;
    }
    
    /**
     * Create instance if configuration is valid
     *
     * @param string|null $libreofficePath Path to libreoffice binary
     * @param string|null $tmpPath         Path for output
     * @param LoggerInterface $logger      Logger
     * @return PdfConverterService|null    Service or null if not valid
     */
    public static function create(
        string $libreofficePath = null, ?string $tmpPath = null, LoggerInterface $logger = null
    )
    {
        if (!empty($libreofficePath) && file_exists($libreofficePath) && is_readable($libreofficePath)) {
            return new self($libreofficePath, $tmpPath, $logger);
        } else {
            return null;
        }
    }
    
    /**
     * Convert word document to pdf and provide file path
     *
     * @param string $input Input document
     * @return string Result
     */
    public function convert(string $input): string
    {
        if (!is_readable($input) || is_dir($input)) {
            throw new InputFileNotFoundException(sprintf('Input file "%s" missing', $input));
        }

        if (!file_exists($this->libreofficePath)) {
            throw new \RuntimeException('Configured libreoffice binary inaccessible');
        }
        if (!is_executable($this->libreofficePath)) {
            throw new \RuntimeException('Configured libreoffice binary not executable');
        }
        
        
        $cli = sprintf(
            '%s --headless --convert-to pdf:writer_pdf_Export -env:UserInstallation=file://%s/LibreOffice_Conversion_${USER} --outdir %s %s',
            $this->libreofficePath,
            $this->tmpPath,
            $this->tmpPath,
            escapeshellarg($input)
        );
        
        $time = microtime(true);
        exec(
            $cli,
            $output,
            $returnVar
        );
        
        if ($returnVar !== 0) {
            throw new \RuntimeException(sprintf('Conversion failed with %d: %s', $returnVar, implode(', ', $output)));
        }
        $this->logger->info(
            'Converted {source} to PDF within {time} s', ['source' => $input, 'time' => (int)(microtime(true) - $time)]
        );
        
        $filename = basename($input);
        
        $filename = preg_replace('/\.[^.]+$/', '.' . 'pdf', $filename);
        
        $result = $this->tmpPath . DIRECTORY_SEPARATOR . $filename;
        
        if (!file_exists($result)) {
            throw new \RuntimeException('Output file not existing');
        }
        if (filesize($result) < 5) {
            throw new \RuntimeException('Output file seems to be empty');
        }
        
        return $result;
    }
    
}
