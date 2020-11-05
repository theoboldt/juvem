<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Export\Sheet\ParticipantsSheet;
use AppBundle\Twig\GlobalCustomization;

class ParticipantsExport extends Export
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
     * @param GlobalCustomization $customization Customization provider in order to eg. add company information
     * @param Event               $event         Event to export
     * @param array               $participants  List of participants qualified for export
     * @param User|null           $modifier      Modifier/creator of export
     */
    public function __construct($customization, Event $event, array $participants, User $modifier)
    {
        $this->event        = $event;
        $this->participants = $participants;

        parent::__construct($customization, $modifier);
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

        $participantsSheet = new ParticipantsSheet($sheet, $this->event, $this->participants);
        $participantsSheet->process();

        $sheet->setTitle('Teilnehmer');

        parent::process();
    }


}