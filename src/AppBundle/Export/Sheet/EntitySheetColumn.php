<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;

class EntitySheetColumn extends AbstractSheetColumn
{

	/**
	 * Contains the data attribute of the related entity
	 *
	 * @var    string
	 */
	protected $dataAttribute;

	public function __construct($dataAttribute, $title)
	{
		$this->dataAttribute = $dataAttribute;

		parent::__construct($dataAttribute, $title);
	}

	/**
	 * Get data attribute of related entity
	 *
	 * @return string
	 */
	public function getDataAttribute()
	{
		return $this->dataAttribute;
	}

	/**
	 * Get value by identifier of this column for transmitted entity
	 *
	 * @param    Object $entity Entity
	 * @return mixed
	 */
	public function getData($entity)
	{
		$accessor = 'get' . ucfirst($this->dataAttribute);

		if (!method_exists($entity, $accessor)) {
			throw new \InvalidArgumentException('Transmitted entity has not expected accessor');
		}
		return $entity->$accessor();

	}

	/**
	 * Write element to excel file
	 *
	 * @param \PHPExcel_Worksheet $sheet Excel sheet to write
	 * @param integer $row Current row
	 * @param Object $entity Entity to process
	 */
	public function process($sheet, $row, $entity)
	{
		$value = $this->getData($entity);

		$sheet->setCellValueByColumnAndRow($this->columnIndex, $row, $value);

	}

}