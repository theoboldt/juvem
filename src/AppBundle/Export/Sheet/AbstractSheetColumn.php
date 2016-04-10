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
	 * @var	null|string
	 */
	protected $numberFormat = null;

	/**
	 * May contain a converter for given value
	 *
	 * @var null|callable
	 */
	protected $converter = null;

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


}