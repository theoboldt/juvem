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
use AppBundle\Export\Sheet\ParticipationsSheet;
use AppBundle\Twig\GlobalCustomization;

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

    /**
     * ParticipationsExport constructor.
     *
     * @param GlobalCustomization $customization  Customization provider in order to eg. add company information
     * @param Event               $event          Event to export
     * @param array               $participations List of participations qualified for export
     * @param User|null           $modifier       Modifier/creator of export
     */
    public function __construct($customization, Event $event, array $participations, User $modifier)
    {
        $this->event          = $event;
        $this->participations = $participations;

        parent::__construct($customization, $modifier);
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