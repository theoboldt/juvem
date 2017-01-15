<?php
namespace AppBundle\Export;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Export\Sheet\ParticipantsMailSheet;
use AppBundle\Export\Sheet\ParticipationsSheet;
use AppBundle\Twig\GlobalCustomization;

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

    /**
     * ParticipationsExport constructor.
     *
     * @param GlobalCustomization $customization  Customization provider in order to eg. add company information
     * @param Event               $event          Event to export
     * @param array               $participants   List of participants qualified for export
     * @param array               $participations List of participations qualified for export
     * @param User|null           $modifier       Modifier/creator of export
     */
    public function __construct(
        $customization, Event $event, array $participants, array $participations, User $modifier
    )
    {
        $this->event          = $event;
        $this->participants   = $participants;
        $this->participations = $participations;

        parent::__construct($customization, $modifier);
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