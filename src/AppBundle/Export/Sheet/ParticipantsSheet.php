<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;

class ParticipantsSheet extends AbstractSheet
{

    /**
     * The event this participant export belongs to
     *
     * @var Event
     */
    protected $event;

    public function __construct(\PHPExcel_Worksheet $sheet, Event $event)
    {
        $this->event = $event;

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

    }


}