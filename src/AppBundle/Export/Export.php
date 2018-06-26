<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export;


use AppBundle\Entity\User;
use AppBundle\Twig\GlobalCustomization;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPExcel_Worksheet_PageSetup;

abstract class Export
{

    /**
     * Timestamp of export
     *
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * The user who caused the creation of this export
     *
     * @var User
     */
    protected $modifier;

    /**
     * Excel document
     *
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    protected $document;

    /**
     * Current sheet index
     *
     * @var integer
     */
    public $sheetIndex = null;

    /**
     * Current sheet
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    public $sheet;

    /**
     * Customization provider
     *
     * @var GlobalCustomization
     */
    protected $customization;

    /**
     * Export constructor.
     *
     * @param GlobalCustomization $customization Customization provider in order to eg. add company information
     * @param User|null           $modifier      Modifier/creator of export
     */
    public function __construct($customization, User $modifier = null)
    {
        $this->customization = $customization;
        $this->modifier      = $modifier;
        $this->timestamp     = new \DateTime();
        $this->document      = new Spreadsheet();
    }

    /**
     * Setup metadata for exported file
     */
    public function setMetadata()
    {
        $this->document->getProperties()
                       ->setCreator($this->customization->organizationName())
                       ->setCompany($this->customization->organizationName())
                       ->setCategory('Juvem')
                       ->setCustomProperty('generationDuration', $this->generationDuration(), \PHPExcel_DocumentProperties::PROPERTY_TYPE_INTEGER);

        if ($this->modifier) {
            $name = $this->modifier->fullname();
            $this->document->getProperties()->setCreator($name);
            $this->document->getProperties()->setLastModifiedBy($name);
        }
    }

    /**
     * Get export generation duration in seconds
     *
     * @return int
     */
    private function generationDuration()
    {
        $now = new \DateTime();
        return (int)$now->format('U') - (int)$this->getTimestamp()->format('U');
    }

    /**
     * Add a new sheet to the excel file and return it
     *
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     * @throws \PHPExcel_Exception
     */
    public function addSheet()
    {
        if ($this->sheetIndex === null) {
            $this->sheetIndex = 0;
        } else {
            $this->document->createSheet();
            ++$this->sheetIndex;
        }

        $this->document->setActiveSheetIndex($this->sheetIndex);
        $this->sheet = $this->document->getActiveSheet();
        $this->sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

        return $this->sheet;
    }

    /**
     * Execute data processing
     */
    public function process()
    {
        $this->setMetadata();
    }

    /**
     * Get phpexcel document
     *
     * @return PHPExcel
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Get export timestamp
     *
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }


    /**
     * Write result to transmitted path of file
     *
     * @param string $path Path to result file
     */
    public function write($path)
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->document);
        $writer->save($path);
    }


}