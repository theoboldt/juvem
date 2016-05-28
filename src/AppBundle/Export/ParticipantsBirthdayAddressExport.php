<?php
namespace AppBundle\Export;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Export\Sheet\ParticipantsBirthdayAddressSheet;

class ParticipantsBirthdayAddressExport extends Export
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

    public function __construct(Event $event, array $participants, User $modifier)
    {
        $this->event        = $event;
        $this->participants = $participants;

        parent::__construct($modifier);
    }

    public function setMetadata()
    {
        parent::setMetadata();

        $this->document->getProperties()
                       ->setTitle('Teilnehmerliste');
        $this->document->getProperties()
                       ->setSubject($this->event->getTitle());
        $this->document->getProperties()
                       ->setDescription(sprintf('Teilnehmerliste für Veranstaltung "%s"', $this->event->getTitle()));
    }

    public function process()
    {

        $sheet = $this->addSheet();

        $participantsSheet = new ParticipantsBirthdayAddressSheet($sheet, $this->event, $this->participants);
        $participantsSheet->process();

        $sheet->setTitle('Teilnehmer');

        parent::process();
    }


}