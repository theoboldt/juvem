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


use AppBundle\BitMask\ParticipantFood;
use AppBundle\Entity\AcquisitionAttribute;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;

class ParticipantsSheet extends ParticipantsSheetBase
{

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
            function (\DateTime $value, $entity) {
                return \PHPExcel_Shared_Date::FormattedPHPToExcel(
                    $value->format('Y'), $value->format('m'), $value->format('d')
                );
            }
        );
        $column->setWidth(10);
        $this->addColumn($column);

        $column = new EntitySheetColumn('ageAtEvent', 'Alter');
        $column->setNumberFormat('#,##0.0');
        $column->setWidth(4);
        $this->addColumn($column);

        $column = EntitySheetColumn::createSmallColumn('gender', 'Geschlecht');
        $column->setConverter(
            function ($value, Participant $entity) {
                return substr($entity->getGender(true), 0, 1);
            }
        );
        $this->addColumn($column);

        $column = EntitySheetColumn::createYesNoColumn('food_vegetarian', 'Vegetarisch', 'food');
        $column->setConverter(
            function ($value, Participant $entity) {
                $mask = $entity->getFood(true);
                return $mask->has(ParticipantFood::TYPE_FOOD_VEGETARIAN) ? 'vs' : 'nein';
            }
        );
        $this->addColumn($column);

        $column = EntitySheetColumn::createYesNoColumn('food_lactose_free', 'Laktosefrei', 'food');
        $column->setConverter(
            function ($value, Participant $entity) {
                $mask = $entity->getFood(true);
                return $mask->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE) ? 'lf' : 'nein';
            }
        );
        $this->addColumn($column);

        $column = EntitySheetColumn::createYesNoColumn('food_lactose_no_pork', 'Ohne Schwein', 'food');
        $column->setConverter(
            function ($value, Participant $entity) {
                $mask = $entity->getFood(true);
                return $mask->has(ParticipantFood::TYPE_FOOD_NO_PORK) ? 'os' : 'nein';
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

        /** @var AcquisitionAttribute $attribute */
        foreach ($event->getAcquisitionAttributes(false, true) as $attribute) {
            $this->addColumn(new EntitySheetColumn('acq_field_'.$attribute->getBid(), $attribute->getManagementTitle()));
        }
        //TODO subject to change

        $column = new EntitySheetColumn('createdAt', 'Eingang Anmeldung');
        $column->setNumberFormat('dd.mm.yyyy hh:mm');
        $column->setConverter(
            function (\DateTime $value, $entity) {
                return \PHPExcel_Shared_Date::FormattedPHPToExcel(
                    $value->format('Y'), $value->format('m'), $value->format('d'),
                    $value->format('H'), $value->format('i')
                );
            }
        );
        $column->setWidth(14);
        $this->addColumn($column);

        $column = new EntitySheetColumn('aid', 'AID');
        $column->setWidth(4);
        $this->addColumn($column);

        $column = new EntitySheetColumn('participation', 'PID');
        $column->setConverter(
            function (Participation $value) {
                return $value->getPid();
            }
        );
        $column->setWidth(4);
        $this->addColumn($column);

    }

}