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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntityColumn extends AbstractColumn
{

	/**
	 * Contains the data attribute of the related entity
	 *
	 * @var    string
	 */
	protected $dataAttribute;

    /**
     * Contains the symfony property accessor used in order to read data from entity
     *
     * @var PropertyAccessor
     */
	private $accessor;

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

        $conditional = new Conditional();
        $conditional->setConditionType(Conditional::CONDITION_CONTAINSTEXT);
        $conditional->setOperatorType(Conditional::OPERATOR_CONTAINSTEXT)->setText('nein');
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
        $column->addHeaderStyleCallback(function($style){
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
     * @param   Object  $entity Entity
     * @return  mixed
     */
	public function getData($entity)
	{
	    if (!$this->accessor) {
    	    $this->accessor = PropertyAccess::createPropertyAccessor();
        }
        return $this->accessor->getValue($entity, $this->dataAttribute);
	}

	/**
	 * Write element to excel file
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Excel sheet to write
	 * @param integer $row Current row
	 * @param mixed $entity Entity to process
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