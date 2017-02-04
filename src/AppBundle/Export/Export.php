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
use PHPExcel;
use PHPExcel_Worksheet_PageSetup;
use PHPExcel_Writer_Excel2007;

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
     * @var PHPExcel
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
     * @var \PHPExcel_Worksheet
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
        $this->document      = new PHPExcel();
    }

    /**
     * Setup metadata for exported file
     */
    public function setMetadata()
    {
        $this->document->getProperties()
                       ->setCreator($this->customization->organizationName());
        $this->document->getProperties()
                       ->setCompany($this->customization->organizationName());
        $this->document->getProperties()
                       ->setCategory('Juvem');
        if ($this->modifier) {
            $name = $this->modifier->fullname($this->modifier->getNameLast(), $this->modifier->getNameFirst());
            $this->document->getProperties()->setCreator($name);
            $this->document->getProperties()->setLastModifiedBy($name);
        }
    }

    /**
     * Add a new sheet to the excel file and return it
     *
     * @return \PHPExcel_Worksheet
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
     * @throws \PHPExcel_Writer_Exception
     */
    public function write($path)
    {
        $writer = new PHPExcel_Writer_Excel2007($this->document);
        $writer->save($path);
    }


}