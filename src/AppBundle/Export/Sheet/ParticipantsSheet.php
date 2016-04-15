<?php
namespace AppBundle\Export\Sheet;


use AppBundle\BitMask\ParticipantFood;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;

class ParticipantsSheet extends AbstractSheet
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


    public function __construct(\PHPExcel_Worksheet $sheet, Event $event, array $participants)
    {
        $this->event        = $event;
        $this->participants = $participants;

        parent::__construct($sheet);

        $this->addColumn(new EntitySheetColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntitySheetColumn('nameLast', 'Nachname'));

        $column = new EntitySheetColumn('birthday', 'Geburtstag');
        $column->setNumberFormat('dd.mm.yyyy');
        $column->setConverter(
            function ($value, $entity) {
                /** \DateTime $value */
                return $value->format('d.m.Y');
            }
        );
        $this->addColumn($column);

        $column = new EntitySheetColumn('ageAtEvent', 'Alter');
        $column->setNumberFormat(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
        $this->addColumn($column);

        $column = EntitySheetColumn::createSmallColumn('gender', 'Geschlecht');
        $column->setConverter(
            function ($value, $entity) {
                return substr($entity->getGender(true), 0, 1);
            }
        );
        $this->addColumn($column);

        $column = EntitySheetColumn::createYesNoColumn('food_vegan', 'Vegan', 'food');
        $column->setConverter(
            function ($value, $entity) {
                /** @var ParticipantFood $mask */
                $mask = $entity->getFood(true);
                return $mask->has(ParticipantFood::TYPE_FOOD_VEGAN) ? 'ja' : 'nein';
            }
        );
        $this->addColumn($column);

        $column = EntitySheetColumn::createYesNoColumn('food_vegetarian', 'Vegetarisch', 'food');
        $column->setConverter(
            function ($value, $entity) {
                /** @var ParticipantFood $mask */
                $mask = $entity->getFood(true);
                return $mask->has(ParticipantFood::TYPE_FOOD_VEGETARIAN) ? 'ja' : 'nein';
            }
        );
        $this->addColumn($column);

        $column = EntitySheetColumn::createYesNoColumn('food_lactose_free', 'Laktosefrei', 'food');
        $column->setConverter(
            function ($value, $entity) {
                /** @var ParticipantFood $mask */
                $mask = $entity->getFood(true);
                return $mask->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE) ? 'ja' : 'nein';
            }
        );
        $this->addColumn($column);

        $column = EntitySheetColumn::createYesNoColumn('food_lactose_no_pork', 'Ohne Schwein', 'food');
        $column->setConverter(
            function ($value, $entity) {
                /** @var ParticipantFood $mask */
                $mask = $entity->getFood(true);
                return $mask->has(ParticipantFood::TYPE_FOOD_NO_PORK) ? 'ja' : 'nein';
            }
        );
        $this->addColumn($column);

        $column = new EntitySheetColumn('infoMedical', 'Medizinische Hinweise');
        $column->addDataStyleCalback(
            function ($style) {
                /** @var \PHPExcel_Style $style */
                $style->getAlignment()->setWrapText(true);
            }
        );
        $column->setWidth(35);
        $this->addColumn($column);

        $column = new EntitySheetColumn('infoGeneral', 'Allgemeine Hinweise');
        $column->addDataStyleCalback(
            function ($style) {
                /** @var \PHPExcel_Style $style */
                $style->getAlignment()->setWrapText(true);
            }
        );
        $column->setWidth(35);
        $this->addColumn($column);

        $column = new EntitySheetColumn('createdAt', 'Eingang Anmeldung');
        $column->setNumberFormat('dd.mm.yyyy h:mm');
        $column->setConverter(
            function ($value, $entity) {
                /** \DateTime $value */
                return $value->format('d.m.Y h:i');
            }
        );
        $column->setWidth(13.5);
        $this->addColumn($column);
    }

    public function setHeader($title = null, $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Teilnehmer');
        $this->row = $this->row - 1; //reset row index by 1
        parent::setColumnHeaders();

        $this->sheet->getRowDimension($this->row(null, false) - 1)->setRowHeight(-1);
    }

    public function setBody()
    {

        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $row = $this->row();

            /** @var EntitySheetColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participant);

                $columnDataConditional = $column->getDataCellConditionals();
                if (count($columnDataConditional)) {
                    $cellStyle->setConditionalStyles($columnDataConditional);
                }
                $cellStyle->getAlignment()->setVertical(
                    \PHPExcel_Style_Alignment::VERTICAL_TOP
                );
                $cellStyle->getBorders()->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

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