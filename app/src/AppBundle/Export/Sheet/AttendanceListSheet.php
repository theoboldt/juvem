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


use AppBundle\Controller\Event\Participation\AdminMultipleExportController;
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AttendanceList\AttendanceList;
use AppBundle\Entity\Participant;
use AppBundle\Export\Sheet\Column\AbstractColumn;
use AppBundle\Export\Sheet\Column\AcquisitionAttributeAttributeColumn;
use AppBundle\Export\Sheet\Column\AttendanceListColumn;
use AppBundle\Export\Sheet\Column\EntityAttributeColumn;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceListSheet extends AbstractSheet
{

    /**
     * The attendance lists (containing event)
     *
     * @var AttendanceList[]
     */
    protected $lists = [];

    /**
     * Stores a list of Participant entities
     *
     * @var Participant[]
     */
    protected $participants;

    /**
     * Filed attendance list data
     *
     * @var array
     */
    private $attendanceData;

    /**
     * Group field
     *
     * @var null|Attribute
     */
    private $groupBy = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        Worksheet $sheet, array $lists, array $participants, array $attendanceData, ?Attribute $groupBy = null
    )
    {
        $this->lists          = $lists;
        $this->participants   = $participants;
        $this->attendanceData = $attendanceData;
        $this->groupBy        = $groupBy;

        parent::__construct($sheet);
        $this->sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

        $this->addColumn(new EntityAttributeColumn('aid', 'AID'));
        $this->addColumn(new EntityAttributeColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntityAttributeColumn('nameLast', 'Nachname'));

        if ($this->groupBy) {
            $column = new AcquisitionAttributeAttributeColumn(
                $this->groupBy->getName(), $this->groupBy->getManagementTitle(), $this->groupBy
            );
            $column->setWidth(12);
            $column->addDataStyleCalback(
                function ($style) {
                    /** @var Style $style */
                    $style->getAlignment()->setShrinkToFit(true);
                }
            );
            $this->addColumn($column);
        }

        $multipleLists = count($this->lists) > 1;
        foreach ($this->lists as $list) {
            foreach ($list->getColumns() as $listColumn) {
                $columnTitle = '';
                if ($multipleLists) {
                    $columnTitle = $list->getTitle() . ' - ';
                }
                $columnTitle .= $listColumn->getTitle();

                $column = new AttendanceListColumn(
                    $list->getTid() . '_column_' . $listColumn->getColumnId(),
                    $columnTitle,
                    $listColumn,
                    $this->attendanceData[$list->getTid()]
                );
                $column->setNumberFormat(NumberFormat::FORMAT_TEXT);
                $column->addHeaderStyleCallback(
                    function ($style) {
                        /** @var Style $style */
                        $style->getAlignment()->setTextRotation(45);
                        $style->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                    }
                );
                $column->addDataStyleCalback(
                    function ($style) {
                        /** @var Style $style */

                        $style->getBorders()->getLeft()->setBorderStyle(Border::BORDER_HAIR);
                        $style->getBorders()->getRight()->setBorderStyle(Border::BORDER_HAIR);
                    }
                );
                $column->setWidth(4);

                $this->addColumn($column);
            }
        }

    }

    /**
     * {@inheritdoc}
     */
    public function setHeader(string $title = null, string $subtitle = null)
    {

        $titles     = [];
        $eventTitle = '';

        foreach ($this->lists as $list) {
            $titles[]   = $list->getTitle();
            $eventTitle = $list->getEvent()->getTitle();
        }

        parent::setHeader(
            implode(', ', $titles), sprintf('Anwesenheitsliste (%s)', $eventTitle)
        );
        parent::setColumnHeaders();
        $this->sheet->getRowDimension(1)->setRowHeight(-1);
    }

    /**
     * {@inheritdoc}
     */
    public function setBody()
    {
        $previousParticipant = null;

        $textualAccessor = AdminMultipleExportController::provideTextualValueAccessor();

        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $groupChange = false;
            $row         = $this->row();

            if ($this->groupBy && $previousParticipant) {
                $currentValue  = $textualAccessor($participant, $this->groupBy->getName());
                $previousValue = $textualAccessor($previousParticipant, $this->groupBy->getName());
                if ($currentValue != $previousValue) {
                    if (!isset($columnIndex)) {
                        $columnIndex = 1;
                    }
                    $groupChange = true;
                    $this->sheet->setBreakByColumnAndRow(1, $row - 1, Worksheet::BREAK_ROW);
                }
            }

            /** @var EntityAttributeColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participant);

                $cellStyle->getAlignment()->setVertical(
                    Alignment::VERTICAL_TOP
                );
                $cellStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                $columnStyles = $column->getDataStyleCallbacks();
                if (count($columnStyles)) {
                    foreach ($columnStyles as $columnStyle) {
                        if (!is_callable($columnStyle)) {
                            throw new \InvalidArgumentException('Defined column style callback is not callable');
                        }
                        $columnStyle($cellStyle);
                    }
                }
                if ($groupChange) {
                    $cellStyle = $this->sheet->getStyleByColumnAndRow($columnIndex, $row - 1);
                    $cellStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
                }
            }

            $previousParticipant = $participant;
        }
        $this->sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, $this->columnMax);
    }

    /**
     * Apply column specific configurations
     *
     * @return void
     */
    private function configureColumnsPostData(): void
    {
        $highestDataRow = $this->rowMax;
        $rowColumnId    = $this->row();
        $rowTableId     = $this->row();

        /** @var AbstractColumn $excelColumn */
        foreach ($this->columnList as $excelColumn) {
            $columnIndexString = Coordinate::stringFromColumnIndex($excelColumn->getColumnIndex());
            $dataRange         = $columnIndexString . ($this->rowHeaderLine + 2) . ':' . $columnIndexString .
                                 ($highestDataRow-1);

            if ($excelColumn instanceof AttendanceListColumn) {
                $listColumn      = $excelColumn->getListColumn();
                $listColumnTitle = $listColumn->getTitle();

                $allowedValues = [];
                $explanations  = [];
                foreach ($listColumn->getChoices() as $choice) {
                    $allowedValues[] = $choice->getShortTitle(true);
                    $explanations[]  = $choice->getShortTitle(true) . ' -> ' . $choice->getTitle();
                }

                $validation = new DataValidation();
                $validation->setErrorStyle(DataValidation::STYLE_STOP)
                           ->setAllowBlank(true)
                           ->setType(DataValidation::TYPE_LIST)
                           ->setFormula1('"' . implode(', ', $allowedValues) . '"')
                           ->setShowDropDown(true)
                           ->setShowErrorMessage(true)
                           ->setErrorTitle('Option für ' . $listColumnTitle . ' nicht verfügbar')
                           ->setError(
                               'Die eingegebene Option ist für ' . $listColumnTitle .
                               ' nicht verfügbar. So kann diese Anwesenheitsliste nicht mehr importiert werden. Wenn eine wichtige Option für ' .
                               $listColumnTitle .
                               ' fehlt, fügen Sie diese in Juvem hinzu, und erstellen Sie den Export erneut.'
                           )
                           ->setShowInputMessage(true)
                           ->setPromptTitle($excelColumn->getListColumn()->getTitle())
                           ->setPrompt(implode(", ", $explanations));

                $this->sheet->setDataValidation(
                    $dataRange,
                    $validation
                );

                //add ids to bottom
                $this->sheet->setCellValueByColumnAndRow(
                    $excelColumn->getColumnIndex(), $rowColumnId, $listColumn->getColumnId()
                );
                $this->sheet->setCellValueByColumnAndRow($excelColumn->getColumnIndex(), $rowTableId, rand(0, 10));
                $style = $this->sheet->getStyleByColumnAndRow(
                    $excelColumn->getColumnIndex(), $rowColumnId, $excelColumn->getColumnIndex(), $rowTableId
                );
                $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color(Color::COLOR_RED));

                //                $this->sheet->getstyle($dataRange)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
            } else {
                /*
                $protection = $this->sheet->getstyle($dataRange)->getProtection();
                $protection->setLocked(Protection::PROTECTION_PROTECTED);
                $this->sheet->protectCells($dataRange, 'php');
                */
            }
        }
        $this->sheet->getColumnDimensionByColumn(1)->setVisible(false);
        $this->sheet->getRowDimension($rowColumnId)->setVisible(false);
        $this->sheet->getRowDimension($rowTableId)->setVisible(false);
    }

    /**
     * Set the footer of this sheet if any
     *
     * @return $this
     */
    public function setFooter()
    {
        parent::setFooter();
        $this->configureColumnsPostData();
    }

    /**
     * Provide footer text
     *
     * @param string|null $title    Title
     * @param string|null $subtitle Sub title
     * @return string
     */
    protected function provideFooterText(string $title = null, string $subtitle = null) {
        return sprintf('&L %s - &B %s &"-,Regular" &R &P/&N', $title, $subtitle);
    }

}
