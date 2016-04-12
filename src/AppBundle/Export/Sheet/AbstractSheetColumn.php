<?php
namespace AppBundle\Export\Sheet;


abstract class AbstractSheetColumn
{
	/**
	 * Column index used in excel sheet
	 *
	 * @var integer
	 */
	protected $columnIndex = null;

	/**
	 * Title or header name of column
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Contains the number formatter which will be applied to each value column
	 *
	 * @var	string|\PHPExcel_Style_NumberFormat::FORMAT_TEXT
	 */
	protected $numberFormat = \PHPExcel_Style_NumberFormat::FORMAT_TEXT;

	/**
	 * May contain a converter for given value
	 *
	 * @var null|callable
	 */
	protected $converter = null;

	/**
	 * Define column width
	 *
	 * @var float|null
	 */
	protected $width = null;

	/**
	 * Callables to be able to apply styles to the header cell
	 *
	 * @var callable[]
	 */
	protected $headerStyleCallbacks = array();

	/**
	 * Callables to be able to apply styles to all data cell
	 *
	 * @var callable[]
	 */
	protected $dataStyleCallbacks = array();

	/**
	 * Array containing conditional style definitions for data cells
	 *
	 * @var \PHPExcel_Style_Conditional[]
	 */
	protected $dataCellConditionals = array();

	/**
	 * Create a new column
	 *
	 * @var string
	 */
	protected $identifier;

	public function __construct($identifier, $title)
	{
		$this->identifier = $identifier;
		$this->title = $title;
	}

	/**
	 * Get identifier of this column for use in colum list
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
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
	 * @return AbstractSheetColumn
	 */
	public function setColumnIndex($columnIndex)
	{
		$this->columnIndex = $columnIndex;
		return $this;
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
	 * @return AbstractSheetColumn
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
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
	 * @return AbstractSheetColumn
	 */
	public function setConverter($converter)
	{
		$this->converter = $converter;
		return $this;
	}

	/**
	 * @param string $numberFormat
	 * @return AbstractSheetColumn
	 */
	public function setNumberFormat($numberFormat)
	{
		$this->numberFormat = $numberFormat;
		return $this;
	}

	/**
	 * Get column width
	 *
	 * @return float|null
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * Set column width
	 *
	 * @param float|null $width
	 * @return AbstractSheetColumn
	 */
	public function setWidth($width)
	{
		$this->width = $width;
		return $this;
	}

	/**
	 * @return callable[]
	 */
	public function getHeaderStyleCallbacks()
	{
		return $this->headerStyleCallbacks;
	}

	/**
	 * @param callable[]
	 * @return AbstractSheetColumn
	 */
	public function addHeaderStyleCallback($headerStyle)
	{
		$this->headerStyleCallbacks[] = $headerStyle;
		return $this;
	}

	/**
	 * @return \callable[]
	 */
	public function getDataStyleCallbacks()
	{
		return $this->dataStyleCallbacks;
	}

	/**
	 * @param \callable[] $dataStyleCallbacks
	 * @return AbstractSheetColumn
	 */
	public function addDataStyleCalback($dataStyleCallbacks)
	{
		$this->dataStyleCallbacks[] = $dataStyleCallbacks;
		return $this;
	}

	/**
	 * @return \PHPExcel_Style_Conditional[]
	 */
	public function getDataCellConditionals()
	{
		return $this->dataCellConditionals;
	}

	/**
	 * @param \PHPExcel_Style_Conditional $dataCellConditional
	 * @return AbstractSheetColumn
	 */
	public function addDataCellConditional($dataCellConditional)
	{
		$this->dataCellConditionals[] = $dataCellConditional;
		return $this;
	}

}