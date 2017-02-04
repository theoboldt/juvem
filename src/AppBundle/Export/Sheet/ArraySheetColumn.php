<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export\Sheet;


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