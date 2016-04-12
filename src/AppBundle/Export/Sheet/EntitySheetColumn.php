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
     * Create a new small column and apply some styles
     *
     * @param string      $identifier       Identifier for document
     * @param string      $title            Title text for column
     * @param string|null $dataAttribute    Name of attribute from witch the data has to be fetched if
     *                                      differing from $identifier
     * @see createSmallColumn()
     * @return self
     */
	public static function createYesNoColumn($identifier, $title, $dataAttribute = null)
    {
        $column = self::createSmallColumn($identifier, $title, $dataAttribute);

        $conditional = new \PHPExcel_Style_Conditional();
        $conditional->setConditionType(\PHPExcel_Style_Conditional::CONDITION_CONTAINSTEXT);
        $conditional->setOperatorType(\PHPExcel_Style_Conditional::OPERATOR_CONTAINSTEXT)->setText('nein');
        $conditional->getStyle()->getFont()->getColor()->setRGB('F2F2F2');

        $column->addDataCellConditional($conditional);

        return $column;
    }

    /**
     * Create a new small column and apply some styles
     *
     * @param string      $identifier       Identifier for document
     * @param string      $title            Title text for column
     * @param string|null $dataAttribute    Name of attribute from witch the data has to be fetched if
     *                                      differing from $identifier
     * @return self
     */
	public static function createSmallColumn($identifier, $title, $dataAttribute = null)
    {
        $column = new self($identifier, $title, $dataAttribute);
        $column->setNumberFormat(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        $column->setHeaderStyleCallback(function($style){
            /** @var \PHPExcel_Style $style */
            $style->getAlignment()->setTextRotation(45);
        });
        $column->setWidth(4);

        return $column;
    }

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