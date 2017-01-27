<?php
namespace AppBundle\Export;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Twig\GlobalCustomization;

class CustomizedExport extends Export
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

    /**
     * ParticipationsExport constructor.
     *
     * @param GlobalCustomization $globalCustomization Customization provider in order to eg. add company information
     * @param Event               $event               Event to export
     * @param array               $participants        List of participants qualified for export
     * @param User|null           $modifier            Modifier/creator of export
     */
    public function __construct($globalCustomization, Event $event, array $participants, User $modifier)
    {
        $this->event        = $event;
        $this->participants = $participants;

        parent::__construct($globalCustomization, $modifier);
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


        parent::process();
    }


}