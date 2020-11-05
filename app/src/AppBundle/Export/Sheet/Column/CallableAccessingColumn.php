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


class CallableAccessingColumn extends AbstractColumn
{

    /**
     * Contains a callable used to access data
     *
     * @var callable
     */
    private $accessor;

    /**
     * Create a new column
     *
     * @param string        $identifier Identifier for document
     * @param string        $title      Title text for column
     * @param callable|null $accessor   Accessor for data
     */
	public function  __construct(string $identifier, string $title, callable $accessor = null)
	{
        $this->accessor = $accessor;

		parent::__construct($identifier, $title);
	}

    /**
     * Contains a callable used to access data
     *
     * @return callable
     */
    public function getAccessor(): callable
    {
        return $this->accessor;
    }

    /**
     * @param callable $accessor
     * @return CallableAccessingColumn
     */
    public function setAccessor(callable $accessor): CallableAccessingColumn
    {
        $this->accessor = $accessor;
        return $this;
    }

	/**
	 * Get value by identifier of this column for transmitted entity
     *
     * @param   object  $entity Entity
     * @return  mixed
     */
	public function getData($entity)
	{
	    $accessor = $this->accessor;

	    if (!is_callable($accessor)) {
	        throw new \InvalidArgumentException('Column has no accessor configured');
        }

	    return $accessor($entity);
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
