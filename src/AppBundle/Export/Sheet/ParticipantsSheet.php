<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;

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

        $this->addColumn(new EntitySheetColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntitySheetColumn('nameLast', 'Nachname'));

        $column = new EntitySheetColumn('birthday', 'Geburtstag');
        $column->setNumberFormat('dd.mm.yyyy');
        $column->setConverter(function($value, $entity){
            /** \DateTime $value */
            return $value->format('d.m.Y');
        });
        $this->addColumn($column);

        $column = new EntitySheetColumn('ageAtEvent', 'Alter');
        $column->setNumberFormat(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
        $this->addColumn($column);

        $column = new EntitySheetColumn('gender', 'Geschlecht');
        $column->setConverter(function($value, $entity){
            return substr($entity->getGender(true), 0, 1);
        });
        $this->addColumn($column);

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

            /** @var EntitySheetColumn $column */
            foreach ($this->columnList as $column) {
                $column->process($this->sheet, $row, $participant);
            }
        }

    }


}