<?php
namespace AppBundle\Export\Sheet;


class EntitySheetColumn extends AbstractSheetColumn
{

	/**
	 * Contains the data attribute of the related entity
	 *
	 * @var    string
	 */
	protected $dataAttribute;

    /**
     * Create a new column
     *
     * @param string      $identifier       Identifier for document
     * @param string      $title            Title text for column
     * @param string|null $dataAttribute    Name of attribute from witch the data has to be fetched if
     *                                      differing from $identifier
     */
	public function __construct($identifier, $title, $dataAttribute = null)
	{
        if ($dataAttribute) {
		$this->dataAttribute = $dataAttribute;
        } else {
            $this->dataAttribute = $identifier;
        }

		parent::__construct($identifier, $title);
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

        if ($this->converter !== null && is_callable($this->converter)) {
            $converter = $this->converter;
            $value     = $converter($value, $entity);
        }

		$sheet->setCellValueByColumnAndRow($this->columnIndex, $row, $value);
		if ($this->numberFormat !== null) {
            $sheet->getStyleByColumnAndRow($this->columnIndex, $row)->getNumberFormat()->setFormatCode(
                $this->numberFormat
            );
        }
    }

}