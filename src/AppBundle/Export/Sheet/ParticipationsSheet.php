<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\PhoneNumber;
use libphonenumber\PhoneNumberUtil;

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


    public function __construct(\PHPExcel_Worksheet $sheet, Event $event, array $participations)
    {
        $this->event          = $event;
        $this->participations = $participations;

        parent::__construct($sheet);

        $this->addColumn(new EntitySheetColumn('salution', 'Anrede'));
        $this->addColumn(new EntitySheetColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntitySheetColumn('nameLast', 'Nachname'));

        $this->addColumn(new EntitySheetColumn('addressStreet', 'Straße (Anschrift)'));
        $this->addColumn(new EntitySheetColumn('addressCity', 'Stadt (Anschrift)'));
        $this->addColumn(new EntitySheetColumn('addressZip', 'PLZ (Anschrift)'));

        $this->addColumn(new EntitySheetColumn('email', 'E-Mail'));

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $column          = new EntitySheetColumn('phoneNumbers', 'Telefonnummern');
        $column->setConverter(
            function ($value, $entity) use ($phoneNumberUtil) {
                $numberText  = '';
                $numberCount = count($value);
                $i           = 1;

                /** @var PhoneNumber $number */
                foreach ($value as $number) {
                    $numberText .= $phoneNumberUtil->formatOutOfCountryCallingNumber($number->getNumber(), 'DE');
                    if ($number->getDescription()) {
                        $numberText .= ' (';
                        $numberText .= $number->getDescription();
                        $numberText .= ')';
                    }

                    if ($i++ < $numberCount) {
                        $numberText .= ', ';
                    }
                }

                return $numberText;
            }
        );
        $column->setWidth(13.5);
        $this->addColumn($column);

        $column = new EntitySheetColumn('createdAt', 'Eingang');
        $column->setNumberFormat('dd.mm.yyyy h:mm');
        $column->setConverter(
            function ($value, $entity) {
                /** \DateTime $value */
                return $value->format('d.m.Y H:i');
            }
        );
        $column->setWidth(13.5);
        $this->addColumn($column);

        $column = new EntitySheetColumn('participants', 'Teilnehmer');
        $column->setConverter(
            function ($value, $entity) {
                return count($value);
            }
        );
        $this->addColumn($column);

        $this->addColumn(new EntitySheetColumn('pid', 'PID'));

    }

    public function setHeader($title = null, $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Anmeldungen');
        parent::setColumnHeaders();
    }

    public function setBody()
    {

        /** @var Participant $participation */
        foreach ($this->participations as $participation) {
            $row = $this->row();

            /** @var EntitySheetColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participation);

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