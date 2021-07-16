<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Export\Sheet\Column\EntityAttributeColumn;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParticipantsBirthdayAddressSheet extends AbstractSheet
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
     * {@inheritdoc}
     */
    public function __construct(Worksheet $sheet, Event $event, array $participants)
    {
        $this->event        = $event;
        $this->participants = $participants;

        parent::__construct($sheet);

        $this->addColumn(new EntityAttributeColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntityAttributeColumn('nameLast', 'Nachname'));

        $column = new EntityAttributeColumn('birthday', 'Geburtstag');
        $column->setNumberFormat('dd.mm.yyyy');
        $column->setConverter(
            function (\DateTime $value, $entity) {
                return Date::FormattedPHPToExcel(
                    $value->format('Y'), $value->format('m'), $value->format('d')
                );
            }
        );
        $this->addColumn($column);

        $column = new EntityAttributeColumn('status', 'Anschrift');
        $column->setConverter(
            function ($value, Participant $entity) {
                return sprintf(
                    '%s, %s %s',
                    $entity->getParticipation()->getAddressStreet(),
                    $entity->getParticipation()->getAddressZip(),
                    $entity->getParticipation()->getAddressCity()
                );
            }
        );
        $this->addColumn($column);
    }

    /**
     * {@inheritdoc}
     */
    public function setHeader(string $title = null, string $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Teilnahmen (Zuschussantrag)');
        parent::setColumnHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function setBody()
    {
        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $row = $this->row();

            /** @var EntityAttributeColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participant);

                $cellStyle->getAlignment()->setVertical(
                    Alignment::VERTICAL_TOP
                );
                $cellStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            }
        }
    }
}
