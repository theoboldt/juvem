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
use AppBundle\Entity\Participant;
use AppBundle\Entity\User;
use AppBundle\Export\Export;
use AppBundle\Export\Sheet\CustomizedParticipantsSheet;
use AppBundle\Export\Sheet\ExplanationSheet;
use AppBundle\Export\Sheet\ParticipantsBirthdayAddressSheet;
use AppBundle\Export\Sheet\ParticipationsSheet;
use AppBundle\Export\Sheet\SheetRequiringExplanationInterface;
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

    /**
     * {@inheritdoc}
     */
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
    
    /**
     * Attach additional participation sheet to this export
     *
     * @throws \Exception
     */
    private function processParticipationSheet() {
        $participations = [];
        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $participation = $participant->getParticipation();
            $pid = $participation->getPid();
            if (!isset($participations[$pid])) {
                $participations[$pid] = $participation;
            }
        }
        
        if (!count($participations)) {
            return;
        }
        $sheet = $this->addSheet();
        $participantsSheet = new ParticipationsSheet($sheet, $this->event, $participations);
        $participantsSheet->process();
        $sheet->setTitle('Anmeldungen');
    }
    
    /**
     * Add additional sheet containing participants list with address
     *
     * @throws \Exception
     */
    private function processSubventionRequestSheet() {
        $sheet = $this->addSheet();
        $participantsSheet = new ParticipantsBirthdayAddressSheet($sheet, $this->event, $this->participants);
        $participantsSheet->process();

        $sheet->setTitle('Teilnehmer - Zuschussantrag');
    }
    
    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $worksheet = $this->addSheet();
        $worksheet->setTitle($this->configuration['title']);

        $participantsSheet = new CustomizedParticipantsSheet(
            $worksheet, $this->event, $this->participants, $this->configuration
        );
        $participantsSheet->process();
    
        if ($this->configuration['additional_sheet']) {
            foreach ($this->configuration['additional_sheet'] as $sheetKey => $sheetEnabled) {
                if ($sheetEnabled) {
                    switch ($sheetKey) {
                        case 'participation';
                            $this->processParticipationSheet();
                            break;
                        case 'subvention_request':
                            $this->processSubventionRequestSheet();
                            break;
                    }
                }
            }
        }

        if ($participantsSheet instanceof SheetRequiringExplanationInterface
            && count($participantsSheet->getExplanations())) {
            $worksheet        = $this->addSheet();
            $explanationSheet = new ExplanationSheet(
                $worksheet, $this->event->getTitle(), $participantsSheet->getExplanations()
            );
            $explanationSheet->process();
        }

        $this->document->setActiveSheetIndex(0);

        parent::process();
    }

}
