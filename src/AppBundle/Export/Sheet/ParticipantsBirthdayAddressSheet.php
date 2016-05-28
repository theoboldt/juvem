<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;

class ParticipantsBirthdayAddressSheet extends AbstractSheet
{

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


    public function __construct(\PHPExcel_Worksheet $sheet, Event $event, array $participants)
    {
        $this->event        = $event;
        $this->participants = $participants;

        parent::__construct($sheet);

        $this->addColumn(new EntitySheetColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntitySheetColumn('nameLast', 'Nachname'));

        $column = new EntitySheetColumn('birthday', 'Geburtstag');
        $column->setNumberFormat('dd.mm.yyyy');
        $column->setConverter(
            function (\DateTime $value, $entity) {
                return \PHPExcel_Shared_Date::FormattedPHPToExcel(
                    $value->format('Y'), $value->format('m'), $value->format('d')
                );
            }
        );
        $this->addColumn($column);

        $column = new EntitySheetColumn('status', 'Anschrift');
        $column->setConverter(
            function ($value, Participant $entity) {
                return sprintf(
                    '%s, %s %s',
                    $entity->getParticipation()->getAddressStreet(),
                    $entity->getParticipation()->getAddressZip(),
                    $entity->getParticipation()->getAddressCity()
                );
            }
        );
        $this->addColumn($column);
    }

    public function setHeader($title = null, $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Teilnehmer');
        $this->row = $this->row - 1; //reset row index by 1
        parent::setColumnHeaders();

        $this->sheet->getRowDimension($this->row(null, false) - 1)->setRowHeight(-1);
    }

    public function setBody()
    {

        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $row = $this->row();

            /** @var EntitySheetColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participant);

                $cellStyle->getAlignment()->setVertical(
                    \PHPExcel_Style_Alignment::VERTICAL_TOP
                );
                $cellStyle->getBorders()->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            }
        }
    }

}