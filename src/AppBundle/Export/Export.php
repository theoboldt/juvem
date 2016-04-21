<?php
namespace AppBundle\Export;


use AppBundle\Entity\User;
use PHPExcel;
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


    public function __construct(User $modifier = null)
    {
        $this->timestamp = new \DateTime();
        $this->document  = new PHPExcel();
        $this->modifier  = $modifier;
    }

    public function setMetadata()
    {
        $this->document->getProperties()
                       ->setCreator('Evangelisches Jugendwerk Stuttgart Vaihingen');
        if ($this->modifier) {
            $name = $this->modifier->fullname($this->modifier->getNameLast(), $this->modifier->getNameFirst());
            $this->document->getProperties()
                           ->setLastModifiedBy($name);
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

        return $this->sheet;
    }

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