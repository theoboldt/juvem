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


use AppBundle\Entity\Participant;
use AppBundle\Manager\CommentManager;
use AppBundle\Manager\Payment\PaymentManager;
use libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpWord\IOFactory;
use Skies\QRcodeBundle\Generator\Generator as BarcodeGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     * Router used to create the routes for the transmitted pages
     *
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;
    
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
     * PaymentManager
     *
     * @var PaymentManager
     */
    private $paymentManager;
    
    /**
     * ParticipantProfileGenerator constructor.
     *
     * @param string $tmpDir
     * @param string $webDir
     * @param UrlGeneratorInterface $urlGenerator
     * @param BarcodeGenerator $barcodeGenerator
     * @param CommentManager $commentManager
     * @param PaymentManager $paymentManager
     * @param PhoneNumberUtil $phoneUtil
     */
    public function __construct(
        string $tmpDir, 
        string $webDir,
        UrlGeneratorInterface $urlGenerator,
        BarcodeGenerator $barcodeGenerator,
        CommentManager $commentManager,
        PaymentManager $paymentManager,
        PhoneNumberUtil $phoneUtil
    )
    {
        $this->tmpDir           = $tmpDir;
        $this->webDir           = $webDir;
        $this->urlGenerator     = $urlGenerator;
        $this->barcodeGenerator = $barcodeGenerator;
        $this->commentManager   = $commentManager;
        $this->paymentManager   = $paymentManager;
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
     * @param array $configuration              Array containing configuration options defined in {@see Configuration}
     * @return string  Path of export file
     */
    public function generate(array $participants, array $configuration)
    {
        $barCodeGenerator = new TemporaryBarCodeGenerator($this->tmpDir, $this->barcodeGenerator);
        
        $profile  = new ParticipantProfile(
            $participants,
            $configuration,
            $this->urlGenerator,
            $this->phoneUtil,
            $this->commentManager,
            $this->paymentManager,
            $barCodeGenerator,
            $this->provideLogoPath()
        );
        $document = $profile->generate();
        
        $tmpPath   = tempnam($this->tmpDir, 'profile_');
        $objWriter = IOFactory::createWriter($document, 'Word2007');
        $objWriter->save($tmpPath);
        
        $profile->cleanup();
        
        return $tmpPath;
    }
}
