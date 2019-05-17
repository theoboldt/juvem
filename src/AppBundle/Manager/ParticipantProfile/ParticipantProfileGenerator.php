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


use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Manager\CommentManager;
use libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpSpreadsheet\Comment;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;
use Skies\QRcodeBundle\Generator\Generator as BarcodeGenerator;

class ParticipantProfileGenerator
{
    
    /**
     * Temporary dir for creating export
     *
     * @var string
     */
    private $tmpDir;
    
    /**
     * Web dir for logo image
     *
     * @var string
     */
    private $webDir;
    
    /**
     * Barcode generator for phone numbers and links
     *
     * @var BarcodeGenerator
     */
    private $barcodeGenerator;
    
    /**
     * phoneUtil
     *
     * @var PhoneNumberUtil
     */
    private $phoneUtil;
    
    /**
     * Comment provider
     *
     * @var CommentManager
     */
    private $commentManager;
    
    /**
     * ParticipantProfileGenerator constructor.
     *
     * @param string $tmpDir
     * @param string $webDir
     * @param BarcodeGenerator $barcodeGenerator
     * @param CommentManager $commentManager
     * @param PhoneNumberUtil $phoneUtil
     */
    public function __construct(
        string $tmpDir, string $webDir, BarcodeGenerator $barcodeGenerator, CommentManager $commentManager,
        PhoneNumberUtil $phoneUtil
    )
    {
        $this->tmpDir           = $tmpDir;
        $this->webDir           = $webDir;
        $this->barcodeGenerator = $barcodeGenerator;
        $this->commentManager   = $commentManager;
        $this->phoneUtil        = $phoneUtil;
    }
    
    /**
     * Provide logo path if exists
     *
     * @return string|null
     */
    private function provideLogoPath(): ?string
    {
        foreach (['favicon-96x96.png', 'favicon-32x32.png', 'favicon-16x16.png'] as $file) {
            if (file_exists($this->webDir . '/' . $file)) {
                return $this->webDir . '/' . $file;
            }
            
        }
        return null;
    }
    
    /**
     * Generate document, provide export file path
     *
     * @param array|Participant[] $participants List of participants for export
     * @return string  Path of export file
     */
    public function generate(array $participants)
    {
        
        $profile  = new ParticipantProfile(
            $participants, $this->phoneUtil, $this->commentManager, $this->provideLogoPath()
        );
        $document = $profile->generate();
        
        $tmpPath   = tempnam($this->tmpDir, 'profile_');
        $objWriter = IOFactory::createWriter($document, 'Word2007');
        $objWriter->save($tmpPath);
        
        $profile->cleanup();
        
        return $tmpPath;
    }
}