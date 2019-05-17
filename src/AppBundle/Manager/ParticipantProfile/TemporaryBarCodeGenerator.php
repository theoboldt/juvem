<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\ParticipantProfile;


use Skies\QRcodeBundle\Generator\Generator;

class TemporaryBarCodeGenerator
{
    /**
     * List of files
     *
     * @var array|string[]
     */
    private $files = [];
    
    /**
     * Temporary dir for created bar codes
     *
     * @var string
     */
    private $tmpDir;
    
    /**
     * Barcode generator for phone numbers and links
     *
     * @var Generator
     */
    private $barcodeGenerator;
    
    /**
     * TemporaryBarCodeGenerator constructor.
     *
     * @param string $tmpDir
     * @param Generator $barcodeGenerator
     */
    public function __construct(string $tmpDir, Generator $barcodeGenerator)
    {
        $this->tmpDir           = $tmpDir;
        $this->barcodeGenerator = $barcodeGenerator;
    }
    
    /**
     * Generate code
     *
     * @param string $code Textual code
     * @return string
     */
    public function createCode(string $code)
    {
        $options  = [
            'code'   => $code,
            'type'   => 'qrcode',
            'format' => 'png',
            'width'  => 16,
            'height' => 16,
            'color'  => [0, 0, 0],
        ];
        $code     = $this->barcodeGenerator->generate($options);
        $filename = tempnam($this->tmpDir, '_code');
        file_put_contents($filename, base64_decode($code));
        $this->files[] = $filename;
        
        return $filename;
    }
    
    /**
     * Cleanup files
     *
     * @return void
     */
    public function cleanup(): void
    {
        while (count($this->files)) {
            $file = array_shift($this->files);
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}