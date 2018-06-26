<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export\Sheet\Column;


use PhpOffice\PhpSpreadsheet\Style\Conditional;

abstract class AbstractColumn
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
	 * @var Conditional[]
	 */
	protected $dataCellConditionals = array();

	/**
	 * Create a new column
	 *
	 * @var string
	 */
	protected $identifier;

    /**
     * AbstractColumn constructor.
     *
     * @param string $identifier
     * @param string $title
     */
    public function __construct($identifier, $title)
    {
        $this->identifier = $identifier;
        $this->title      = $title;
    }

	/**
	 * Get identifier of this column for use in column list
	 *
	 * @return string
	 */
	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return int
	 */
	public function getColumnIndex(): ?int
	{
		return $this->columnIndex;
	}

	/**
	 * @param int $columnIndex
	 * @return AbstractColumn
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
	 * @return AbstractColumn
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
	 * @return AbstractColumn
	 */
	public function setConverter($converter)
	{
		$this->converter = $converter;
		return $this;
	}

	/**
	 * @param string $numberFormat
	 * @return AbstractColumn
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
	 * @return AbstractColumn
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
	 * @return AbstractColumn
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
	 * @param \callable $dataStyleCallbacks
	 * @return AbstractColumn
	 */
	public function addDataStyleCalback($dataStyleCallbacks)
	{
		$this->dataStyleCallbacks[] = $dataStyleCallbacks;
		return $this;
	}

	/**
	 * @return Conditional[]
	 */
	public function getDataCellConditionals()
	{
		return $this->dataCellConditionals;
	}

	/**
	 * @param Conditional $dataCellConditional
	 * @return AbstractColumn
	 */
	public function addDataCellConditional($dataCellConditional)
	{
		$this->dataCellConditionals[] = $dataCellConditional;
		return $this;
	}

}