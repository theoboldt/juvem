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


use AppBundle\Export\Sheet\Column\AbstractColumn;

abstract class AbstractSheet
{

    /**
     * Current sheet
     *
     * @var \PHPExcel_Worksheet
     */
    protected $sheet;

    /**
     * Contains the indexed data column list
     *
     * @var array
     */
    protected $columnList = array();

    /**
     * Current column index
     *
     * @var integer
     */
    protected $column = 0;

    /**
     * Maximum of column index
     *
     * @var integer
     */
    protected $columnMax = 0;

    /**
     * Current row index
     *
     * @var integer
     */
    protected $row = 1;

    /**
     * Maximum of row index
     *
     * @var integer
     */
    protected $rowMax = 1;

    /**
     * Stores the row index of the header line
     *
     * @var integer
     */
    protected $rowHeaderLine = null;

    /**
     * Date of file creation
     *
     * @var \DateTimeImmutable
     */
    protected $created;

    /**
     * AbstractSheet constructor.
     *
     * @param \PHPExcel_Worksheet $sheet
     * @throws \Exception In case @see \DateTimeImmutable() creation fails
     */
    public function __construct(\PHPExcel_Worksheet $sheet)
    {
        $this->created = new \DateTimeImmutable();
        $this->sheet = $sheet;

        $this->sheet->getPageSetup()
                    ->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4)
            ->setRowsToRepeatAtTop(1)
            ->setColumnsToRepeatAtLeft(1)
        ;

        $this->sheet->getPageMargins()
                    ->setTop(0.75)
                    ->setRight(0.75)
                    ->setLeft(0.75)
                    ->setBottom(0.75);

    }

    /**
     * Get formatted date of file export
     *
     * @return string
     */
    protected function getCreatedDateFormatted() {
        return $this->created->format('d.m.y H:i');
    }

    /**
     * Add a data column definition to this sheet
     *
     * @param AbstractColumn $column
     * @return $this
     */
    public function addColumn(AbstractColumn $column)
    {
        if (array_key_exists($column->getIdentifier(), $this->columnList)) {
            throw new \OutOfBoundsException('Tried to add a column but transmitted index is already in use');
        }

        $this->columnList[$column->getIdentifier()] = $column;

        return $this;
    }

    /**
     * Get the current column index
     *
     * @param  int|null $index    If set to null, the current column index will be used. If a integer is defined,
     *                            the transmitted value will be used and the index
     * @param  bool     $increase If set to true, the current column index returned but the index is increased
     * @return int                Get the current column index
     */
    protected function column($index = null, $increase = true)
    {
        if ($index === null) {
            $column = $this->column;

            if ($increase) {
                ++$this->column;
            }
        } else {
            $column = $index;

            if ($increase) {
                $this->column = $column + 1;
            }
        }


        if ($this->column > $this->columnMax) {
            $this->columnMax = $this->column;
        }

        return $column;
    }

    /**
     * Get the current row index
     *
     * @param  int|null $index    If set to null, the current row index will be used. If a integer is defined,
     * @param bool      $increase If set to true, the current row index returned but the index is increased
     * @return int           Get the current row index
     */
    protected function row($index = null, $increase = true)
    {
        if ($index === null) {
            $row = $this->row;

            if ($increase) {
                ++$this->row;
            }
        } else {
            $row = $index;

            if ($increase) {
                $this->row = $row + 1;
            }
        }

        if ($this->row > $this->rowMax) {
            $this->rowMax = $this->row;
        }

        return $row;
    }

    /**
     * Set the header of this sheet
     *
     * @return $this
     */
    public function setHeader($title = null, $subtitle = null)
    {
        $sheet = $this->sheet;

        $headerFooter = $sheet->getHeaderFooter();
        $headerFooter->setDifferentFirst(true);

        if ($title && $subtitle) {
            $firstHeaderTemplate = '&L&K%1$s %2$s &K000000 - &B&K%3$s %4$s &R &8&IExport: %5$s, Druck: &D &T';
        } elseif ($title) {
            $firstHeaderTemplate = '&L&K%1$s %2$s &R &8&IExport: %5$s, Druck: &D &T';
        } elseif ($subtitle) {
            $firstHeaderTemplate = '&L&B&K%3$s %4$s &R &8&IExport: %5$s, Druck: &D &T';
        } else {
            $firstHeaderTemplate = '&R &8&IExport: %5$s, Druck: &D &T';
        }
        $firstHeaderText = sprintf(
            $firstHeaderTemplate,
            '1C639E',
            $title,
            '262626',
            $subtitle,
            $this->getCreatedDateFormatted()
        );
        $headerFooter->setFirstHeader($firstHeaderText);



        $footerText = sprintf('&L %s - &B %s &"-,Regular"(&A) &R &P/&N', $title, $subtitle);
        $headerFooter->setFirstFooter($footerText);
        $headerFooter->setOddFooter($footerText);

        $this->rowHeaderLine = $this->row(0);
        $sheet->getRowDimension($this->rowHeaderLine)
              ->setRowHeight(22);

        return $this;
    }

    /**
     * Set the header of this sheet
     *
     * @return $this
     */
    public function setColumnHeaders()
    {
        $sheet = $this->sheet;

        $row = $this->row();
        $sheet->getRowDimension($row)
              ->setRowHeight(22);

        $columnStart = 0;

        if (!count($this->columnList)) {
            return $this;
        }

        /** @var AbstractColumn $dataColumn */
        foreach ($this->columnList as $dataColumn) {
            $column = $dataColumn->getColumnIndex();
            if ($column === null) {
                $dataColumn->setColumnIndex($this->column());
                $column = $dataColumn->getColumnIndex();
            }

            $sheet->setCellValueByColumnAndRow($column, $row, $dataColumn->getTitle());

            $columnWidth = $dataColumn->getWidth();
            if ($columnWidth === null) {
                $sheet->getColumnDimensionByColumn($column)
                      ->setAutoSize(true);
            } else {
                $sheet->getColumnDimensionByColumn($column)
                      ->setWidth($columnWidth);
            }
            $columnStyles = $dataColumn->getHeaderStyleCallbacks();

            $cellStyle = $sheet->getStyleByColumnAndRow($column, $row);
            $cellStyle->getBorders()->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_DOUBLE);

            if (count($columnStyles)) {
                foreach ($columnStyles as $columnStyle) {
                    if (!is_callable($columnStyle)) {
                        throw new \InvalidArgumentException('Defined column style callback is not callable');
                    }
                    $columnStyle($cellStyle);
                }
            }
        }
        $sheet->getStyleByColumnAndRow($columnStart, $row, $column, $row)
              ->getFont()
              ->setBold(true)
              ->setName('Arial')
              ->setSize(10);

        $sheet->setAutoFilterByColumnAndRow($columnStart, $row, $column, $row);

        return $this;
    }

    /**
     * Set the main content of this sheet
     *
     * @return $this
     */
    public abstract function setBody();

    /**
     * Set the footer of this sheet if any
     *
     * @return $this
     */
    public function setFooter()
    {
        $sheet  = $this->sheet;
        $row    = $this->rowHeaderLine;
        $column = $this->column($this->columnMax);

        $sheet->getColumnDimensionByColumn($column)
              ->setWidth(3);

        $sheet->getStyleByColumnAndRow(0, $row, $column, $row)
              ->applyFromArray(
                  array(
                      'fill' => array(
                          'type'  => \PHPExcel_Style_Fill::FILL_SOLID,
                          'color' => array('rgb' => '1C639E')
                      )
                  )
              );

        return $this;
    }

    /**
     * Process sheet data
     *
     * @return $this
     */
    public function process()
    {
        $this->setHeader(null, null);
        $this->setBody();
        $this->setFooter();

        return $this;
    }

}