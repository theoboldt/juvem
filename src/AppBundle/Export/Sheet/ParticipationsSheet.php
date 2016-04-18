<?php
namespace AppBundle\Export\Sheet;


use AppBundle\BitMask\ParticipationFood;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participation;

class ParticipationsSheet extends AbstractSheet
{

    /**
     * The event this participation export belongs to
     *
     * @var Event
     */
    protected $event;

    /**
     * Stores a list of Participation entities
     *
     * @var array
     */
    protected $participations;


    public function __construct(\PHPExcel_Worksheet $sheet, Event $event, array $participations)
    {
        $this->event        = $event;
        $this->participations = $participations;

        parent::__construct($sheet);

        $this->addColumn(new EntitySheetColumn('salution', 'Anrede'));
        $this->addColumn(new EntitySheetColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntitySheetColumn('nameLast', 'Nachname'));

        $column = new EntitySheetColumn('createdAt', 'Eingang Anmeldung');
        $column->setNumberFormat('dd.mm.yyyy h:mm');
        $column->setConverter(
            function ($value, $entity) {
                /** \DateTime $value */
                return $value->format('d.m.Y H:i');
            }
        );
        $column->setWidth(13.5);
        $this->addColumn($column);
    }

    public function setHeader($title = null, $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Anmeldungen');
        $this->row = $this->row - 1; //reset row index by 1
        parent::setColumnHeaders();

        $this->sheet->getRowDimension($this->row(null, false) - 1)->setRowHeight(-1);
    }

    public function setBody()
    {

        /** @var Participation $participation */
        foreach ($this->participations as $participation) {
            $row = $this->row();

            /** @var EntitySheetColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participation);

                $columnDataConditional = $column->getDataCellConditionals();
                if (count($columnDataConditional)) {
                    $cellStyle->setConditionalStyles($columnDataConditional);
                }
                $cellStyle->getAlignment()->setVertical(
                    \PHPExcel_Style_Alignment::VERTICAL_TOP
                );
                $cellStyle->getBorders()->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

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