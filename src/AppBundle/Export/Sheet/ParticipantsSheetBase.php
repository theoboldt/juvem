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


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Export\Sheet\Column\EntityColumn;
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
     * AbstractSheet constructor.
     *
     * @param Worksheet           $sheet Related @see PHPExcel worksheet
     * @param PaymentManager|null $paymentManager
     */
    public function __construct(Worksheet $sheet, PaymentManager $paymentManager = null)
    {
        $this->paymentManager = $paymentManager;
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
        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $row = $this->row();

            /** @var EntityColumn $column */
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
        }
    }

}
