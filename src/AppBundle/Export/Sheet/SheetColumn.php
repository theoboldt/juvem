<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;

class SheetColumn
{
    /**
     * Column index used in excel sheet
     *
     * @var integer
     */
    protected $columnIndex = null;

    /**
     * Data index in data array
     *
     * @var string
     */
    protected $dataIndex;

    /**
     * Title or header name of column
     *
     * @var string
     */
    protected $title;

    /**
     * May contain a converter for given value
     *
     * @var null|callable
     */
    protected $converter = null;

    public function __construct($dataIndex, $title)
    {
        $this->dataIndex = $dataIndex;
        $this->title     = $title;
    }

    /**
     * @return int
     */
    public function getColumnIndex()
    {
        return $this->columnIndex;
    }

    /**
     * @param int $columnIndex
     */
    public function setColumnIndex($columnIndex)
    {
        $this->columnIndex = $columnIndex;
    }

    /**
     * @return string
     */
    public function getDataIndex()
    {
        return $this->dataIndex;
    }

    /**
     * @param string $dataIndex
     */
    public function setDataIndex($dataIndex)
    {
        $this->dataIndex = $dataIndex;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return callable|null
     */
    public function getConverter()
    {
        return $this->converter;
    }

    /**
     * @param callable|null $converter
     */
    public function setConverter($converter)
    {
        $this->converter = $converter;
    }


}