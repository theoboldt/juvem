<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;

class ArraySheetColumn extends AbstractSheetColumn
{

	/**
	 * Data index in data array
	 *
	 * @var string
	 */
	protected $dataIndex;

	public function __construct($dataIdentifier, $title)
	{
		$this->dataIndex = $dataIdentifier;

		parent::__construct($dataIdentifier, $title);
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

}