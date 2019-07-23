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


use AppBundle\Controller\Event\Participation\AdminMultipleController;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Export\Sheet\Column\EntityAttributeColumn;
use AppBundle\Manager\Payment\PaymentManager;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class ParticipantsSheetBase extends AbstractSheet
{

    /**
     * Payment manager
     *
     * @var PaymentManager|null
     */
    protected $paymentManager;

    /**
     * The event this participant export belongs to
     *
     * @var Event
     */
    protected $event;

    /**
     * Stores a list of Participant entities
     *
     * @var array
     */
    protected $participants;
    
    /**
     * Field to use for grouping
     *
     * @var null|string
     */
    protected $groupBy = null;
    
    /**
     * AbstractSheet constructor.
     *
     * @param Worksheet $sheet     Related @see PHPExcel worksheet
     * @param string|null $groupBy Group by field if set
     * @param PaymentManager|null $paymentManager
     */
    public function __construct(Worksheet $sheet, ?string $groupBy = null, PaymentManager $paymentManager = null)
    {
        $this->paymentManager = $paymentManager;
        $this->groupBy        = $groupBy;
        parent::__construct($sheet);
    }

    /**
     * {@inheritdoc}
     */
    public function setHeader(string $title = null, string $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Teilnehmer');
        parent::setColumnHeaders();

        $this->sheet->getRowDimension($this->row(null, false) - 1)->setRowHeight(-1);
    }

    /**
     * {@inheritdoc}
     */
    public function setBody()
    {
        $previousParticipant = null;
        
        $textualAccessor = AdminMultipleController::provideTextualValueAccessor();
        
        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $row = $this->row();

            /** @var EntityAttributeColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participant);

                $columnDataConditional = $column->getDataCellConditionals();
                if (count($columnDataConditional)) {
                    $cellStyle->setConditionalStyles($columnDataConditional);
                }
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
            }

            if ($this->groupBy && $previousParticipant) {
                $currentValue  = $textualAccessor($participant, $this->groupBy);
                $previousValue = $textualAccessor($previousParticipant, $this->groupBy);
                if ($currentValue != $previousValue) {
                    if (!isset($columnIndex)) {
                        $columnIndex = 1;
                    }
                    $this->sheet->setBreakByColumnAndRow(1, $row-1, Worksheet::BREAK_ROW);
                }
            }

            $previousParticipant = $participant;
        }
        $this->sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, $this->columnMax);
    }

}
