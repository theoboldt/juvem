<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;

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
	 * May contain a converter for given value
	 *
	 * @var null|callable
	 */
	protected $converter = null;

	/**
	 * Identifier of this column for use in colum list
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
	 */
	public function setColumnIndex($columnIndex)
	{
		$this->columnIndex = $columnIndex;
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