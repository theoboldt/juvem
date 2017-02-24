<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export\Customized;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Export\Export;
use AppBundle\Export\Sheet\CustomizedParticipantsSheet;
use AppBundle\Twig\GlobalCustomization;

class CustomizedExport extends Export
{
    /**
     * Customized export configuration
     *
     * @var array
     */
    private $configuration;

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
     * @param array               $configuration       Configuration definition for export,
     *                                                 validated via @see Configuration
     */
    public function __construct(
        $globalCustomization, Event $event, array $participants, User $modifier, array $configuration
    )
    {
        $this->event         = $event;
        $this->participants  = $participants;
        $this->configuration = $configuration;

        parent::__construct($globalCustomization, $modifier);
    }

    public function setMetadata()
    {
        parent::setMetadata();

        $this->document->getProperties()
                       ->setTitle($this->configuration['title']);
        $this->document->getProperties()
                       ->setSubject($this->event->getTitle());
        $this->document->getProperties()
                       ->setDescription(sprintf('Teilnehmerliste für Veranstaltung "%s"', $this->event->getTitle()));
    }

    public function process()
    {
        $sheet = $this->addSheet();

        $participantsSheet = new CustomizedParticipantsSheet(
            $sheet, $this->event, $this->participants, $this->configuration
        );
        $participantsSheet->process();

        $sheet->setTitle($this->configuration['title']);

        parent::process();
    }


}