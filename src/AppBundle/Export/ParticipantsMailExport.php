<?php
namespace AppBundle\Export;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Export\Sheet\ParticipantsMailSheet;
use AppBundle\Export\Sheet\ParticipationsSheet;

class ParticipantsMailExport extends Export
{

    /**
     * The event this participant export belongs to
     *
     * @var Event
     */
    protected $event;

    /**
     * Stores a list of Participants entities
     *
     * @var Participant[]
     */
    protected $participants;

    /**
     * Stores a list of Participation entities
     *
     * @var Participation[]
     */
    protected $participations;

    public function __construct(Event $event, array $participants, array $participations, User $modifier)
    {
        $this->event        = $event;
        $this->participants = $participants;
        $this->participations = $participations;

        parent::__construct($modifier);
    }

    public function setMetadata()
    {
        parent::setMetadata();

        $this->document->getProperties()
                       ->setTitle('Teilnehmerdaten für Serienbrief');
        $this->document->getProperties()
                       ->setSubject($this->event->getTitle());
        $this->document->getProperties()
                       ->setDescription(sprintf('Teilnehmerliste und Anmeldungen für Veranstaltung "%s" für Serienbrief', $this->event->getTitle()));
    }

    public function process()
    {

        $sheet = $this->addSheet();

        $participantsSheet = new ParticipantsMailSheet($sheet, $this->event, $this->participants);
        $participantsSheet->process();
        $sheet->setTitle('Teilnehmer');

        $sheet = $this->addSheet();

        $participantsSheet = new ParticipationsSheet($sheet, $this->event, $this->participations);
        $participantsSheet->process();
        $sheet->setTitle('Anmeldungen');

        parent::process();
    }


}