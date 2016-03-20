<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\User;

class ParticipantsSheet extends AbstractSheet
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

        $this->addColumn(new SheetColumn('nameFirst', 'Vorname'));
        $this->addColumn(new SheetColumn('nameLast', 'Nachname'));
        $this->addColumn(new SheetColumn('birthday', 'Geburtstag'));
    }

    public function setHeader($title = null, $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Teilnehmer');
    }

    public function setBody()
    {

        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $row = $this->row();

            /** @var SheetColumn $column */
            foreach ($this->columnList as $column) {
                $dataIndex = 'get'.ucfirst($column->getDataIndex());
                $value     = $participant->$dataIndex();
                $this->sheet->setCellValueByColumnAndRow($column->getColumnIndex(), $row, $value);
            }
        }

    }


}