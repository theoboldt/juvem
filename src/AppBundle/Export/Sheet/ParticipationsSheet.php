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
use AppBundle\Entity\Participation;
use AppBundle\Export\Sheet\Column\EntityAttributeColumn;
use AppBundle\Export\Sheet\Column\EntityPhoneNumberSheetAttributeColumn;
use AppBundle\Manager\Payment\PaymentManager;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParticipationsSheet extends AbstractSheet
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
     * Payment manager
     *
     * @var PaymentManager|null
     */
    protected $paymentManager;

    /**
     * Stores a list of Participant entities
     *
     * @var array
     */
    protected $participants;

    /**
     * ParticipationsSheet constructor.
     *
     * @param Worksheet           $sheet
     * @param Event               $event
     * @param array               $participations
     * @param PaymentManager|null $paymentManager
     */
    public function __construct(Worksheet $sheet, Event $event, array $participations, PaymentManager $paymentManager = null)
    {
        $this->event          = $event;
        $this->participations = $participations;
        $this->paymentManager = $paymentManager;

        parent::__construct($sheet);

        $this->addColumn(new EntityAttributeColumn('salutation', 'Anrede'));
        $this->addColumn(new EntityAttributeColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntityAttributeColumn('nameLast', 'Nachname'));

        $this->addColumn(new EntityAttributeColumn('addressStreet', 'Straße (Anschrift)'));
        $this->addColumn(new EntityAttributeColumn('addressCity', 'Stadt (Anschrift)'));
        $this->addColumn(new EntityAttributeColumn('addressZip', 'PLZ (Anschrift)'));

        $this->addColumn(new EntityAttributeColumn('email', 'E-Mail'));

        $this->addColumn(
            EntityPhoneNumberSheetAttributeColumn::createCommaSeparated('phoneNumbers', 'Telefonnummern', null, true)
        );

        $column = new EntityAttributeColumn('createdAt', 'Eingang');
        $column->setNumberFormat('dd.mm.yyyy h:mm');
        $column->setConverter(
            function (\DateTime $value, $entity) {
                return Date::FormattedPHPToExcel(
                    $value->format('Y'), $value->format('m'), $value->format('d'),
                    $value->format('H'), $value->format('i')
                );
            }
        );
        $column->setWidth(15);
        $this->addColumn($column);

        $column = new EntityAttributeColumn('participants', 'Teilnehmer');
        $column->setConverter(
            function ($value, $entity) {
                return count($value);
            }
        );
        $this->addColumn($column);

        if ($this->paymentManager && $event->getPrice()) {
            $column = new EntityAttributeColumn('price', 'Preis');
            $column->setNumberFormat('#,##0.00 €');
            $column->setWidth(8);
            $column->setConverter(
                function ($value, Participation $participation) {
                    return $this->paymentManager->getPriceForParticipation($participation, true);
                }
            );
            $this->addColumn($column);
        }

        $this->addColumn(new EntityAttributeColumn('pid', 'PID'));

    }

    /**
     * {@inheritdoc}
     */
    public function setHeader(string $title = null, string $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Anmeldungen');
        parent::setColumnHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function setBody()
    {
        /** @var Participation $participation */
        foreach ($this->participations as $participation) {
            $row = $this->row();

            /** @var EntityAttributeColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participation);

                $columnDataConditional = $column->getDataCellConditionals();
                if (count($columnDataConditional)) {
                    $cellStyle->setConditionalStyles($columnDataConditional);
                }
                $cellStyle->getAlignment()->setVertical(
                    Alignment::VERTICAL_TOP
                );
                $cellStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                $columnStyles = $column->getDataStyleCallbacks();
                if (count($columnStyles)) {
                    foreach ($columnStyles as $columnStyle) {
                        if (!is_callable($columnStyle)) {
                            throw new \InvalidArgumentException('Defined column style callback is not callable');
                        }
                        $columnStyle($cellStyle);
                    }
                }
            }
        }
    }

}
