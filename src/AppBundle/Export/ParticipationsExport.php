<?php
namespace AppBundle\Export;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Export\Sheet\ParticipationsSheet;

class ParticipationsExport extends Export
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

    public function __construct(Event $event, array $participations, User $modifier)
    {
        $this->event        = $event;
        $this->participations = $participations;

        parent::__construct($modifier);
    }

    public function setMetadata()
    {
        parent::setMetadata();

        $this->document->getProperties()
                       ->setTitle('Anmeldungen');
        $this->document->getProperties()
                       ->setSubject($this->event->getTitle());
        $this->document->getProperties()
                       ->setDescription(sprintf('Liste der Anmeldungen für Veranstaltung "%s"', $this->event->getTitle()));
    }

    public function process()
    {

        $sheet = $this->addSheet();

        $participationsSheet = new ParticipationsSheet($sheet, $this->event, $this->participations);
        $participationsSheet->process();

        $sheet->setTitle('Anmeldungen');

        parent::process();
    }


}