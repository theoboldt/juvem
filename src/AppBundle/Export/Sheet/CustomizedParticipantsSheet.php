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
use AppBundle\Entity\AcquisitionAttributeFillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Export\Sheet\Column\AcquisitionAttributeColumn;
use AppBundle\Export\Sheet\Column\EntityColumn;
use AppBundle\Export\Sheet\Column\EntityPhoneNumberSheetColumn;
use AppBundle\Export\Sheet\Column\ParticipationAcquisitionAttributeColumn;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomizedParticipantsSheet extends ParticipantsSheetBase
{
    /**
     * CustomizedParticipantsSheet constructor.
     *
     * @param Worksheet $sheet        The excel workbook to use
     * @param Event               $event        Event to export
     * @param array               $participants List of participants qualified for export
     * @param array               $config       Configuration definition for export, validated
     *                                          via @see \AppBundle\Export\Customized\Configuration
     */
    public function __construct(Worksheet $sheet, Event $event, array $participants, array $config)
    {
        $this->event        = $event;
        $this->participants = $participants;

        parent::__construct($sheet);

        $configParticipant = $config['participant'];

        if (self::issetAndTrue($configParticipant, 'aid')) {
            $column = new EntityColumn('aid', 'AID');
            $column->setWidth(4);
            $this->addColumn($column);
        }

        if (self::issetAndTrue($config['participation'], 'pid')) {
            $column = new EntityColumn('participation', 'PID');
            $column->setConverter(
                function (Participation $value) {
                    return $value->getPid();
                }
            );
            $column->setWidth(4);
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'nameFirst')) {
            $this->addColumn(new EntityColumn('nameFirst', 'Vorname'));
        }
        if (self::issetAndTrue($configParticipant, 'nameLast')) {
            $this->addColumn(new EntityColumn('nameLast', 'Nachname'));
        }

        if (self::issetAndTrue($configParticipant, 'birthday')) {
            $column = new EntityColumn('birthday', 'Geburtstag');
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
        }

        if ($configParticipant['ageAtEvent'] != 'none') {
            $column = new EntityColumn('ageAtEvent', 'Alter');
            $column->setWidth(4);
            switch ($configParticipant['ageAtEvent']) {
                case 'round':
                    $column->setNumberFormat('0');
                    break;
                case 'ceil':
                    $column->setConverter(
                        function ($value, $entity) {
                            return ceil($value);
                        }
                    );
                    $column->setNumberFormat('0');
                    break;
                case 'decimalplace':
                    $column->setNumberFormat('#,##0.0');
                    break;
            }
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'gender')) {
            $column = EntityColumn::createSmallColumn('gender', 'Geschlecht');
            $column->setConverter(
                function ($value, Participant $entity) {
                    return substr($entity->getGender(true), 0, 1);
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'foodVegetarian')) {
            $column = EntityColumn::createYesNoColumn('food_vegetarian', 'Vegetarisch', 'food');
            $column->setConverter(
                function ($value, Participant $entity) {
                    $mask = $entity->getFood(true);
                    return $mask->has(ParticipantFood::TYPE_FOOD_VEGETARIAN) ? 'vs' : 'nein';
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'foodLactoseFree')) {
            $column = EntityColumn::createYesNoColumn('food_lactose_free', 'Laktosefrei', 'food');
            $column->setConverter(
                function ($value, Participant $entity) {
                    $mask = $entity->getFood(true);
                    return $mask->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE) ? 'lf' : 'nein';
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'foodLactoseNoPork')) {
            $column = EntityColumn::createYesNoColumn('food_lactose_no_pork', 'Ohne Schwein', 'food');
            $column->setConverter(
                function ($value, Participant $entity) {
                    $mask = $entity->getFood(true);
                    return $mask->has(ParticipantFood::TYPE_FOOD_NO_PORK) ? 'os' : 'nein';
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'infoMedical')) {
            $column = new EntityColumn('infoMedical', 'Medizinische Hinweise');
            $column->addDataStyleCalback(
                function ($style) {
                    /** @var \PHPExcel_Style $style */
                    $style->getAlignment()->setWrapText(true);
                }
            );
            $column->setWidth(35);
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'infoGeneral')) {
            $column = new EntityColumn('infoGeneral', 'Allgemeine Hinweise');
            $column->addDataStyleCalback(
                function ($style) {
                    /** @var \PHPExcel_Style $style */
                    $style->getAlignment()->setWrapText(true);
                }
            );
            $column->setWidth(35);
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'price') && $event->getPrice()) {
            $column = new EntityColumn('price', 'Preis');
            $column->setNumberFormat('#,##0.00 €');
            $column->setWidth(8);
            $column->setConverter(
                function ($value, Participant $entity) {
                    return $entity->getPrice(true);
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'createdAt')) {
            $column = new EntityColumn('createdAt', 'Eingang Anmeldung');
            $column->setNumberFormat('dd.mm.yyyy hh:mm');
            $column->setConverter(
                function (\DateTime $value, $entity) {
                    return \PHPExcel_Shared_Date::FormattedPHPToExcel(
                        $value->format('Y'), $value->format('m'), $value->format('d'),
                        $value->format('H'), $value->format('i')
                    );
                }
            );
            $column->setWidth(15);
            $this->addColumn($column);
        }

        $this->appendParticipantAcquisitionColumns($event, $configParticipant['acquisitionFields']);


        $configParticipation = $config['participation'];

        if (self::issetAndTrue($configParticipation, 'salution')) {
            $column = new EntityColumn('participation_salution', 'Anrede (Eltern)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getSalution();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'nameFirst')) {
            $column = new EntityColumn('participation_nameFirst', 'Vorname (Eltern)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getNameFirst();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'nameLast')) {
            $column = new EntityColumn('participation_nameLast', 'Nachname (Eltern)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getNameLast();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'addressStreet')) {
            $column = new EntityColumn('participation_addressStreet', 'Straße (Anschrift)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getAddressStreet();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'addressCity')) {
            $column = new EntityColumn('participation_addressCity', 'Stadt (Anschrift)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getAddressCity();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'addressZip')) {
            $column = new EntityColumn('participation_addressZip', 'PLZ (Anschrift)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getAddressZip();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'email')) {
            $column = new EntityColumn('participation_email', 'E-Mail', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getEmail();
                }
            );
            $this->addColumn($column);
        }

        if ($configParticipation['phoneNumber'] != 'none') {
            $this->addColumn(
                EntityPhoneNumberSheetColumn::createCommaSeparated(
                    'phoneNumbers',
                    'Telefonnummern',
                    'participation',
                    $configParticipation['phoneNumber'] == 'comma_description' ? true : null)
            );
        }

        $this->appendParticipationAcquisitionColumns($event, $configParticipation['acquisitionFields']);
    }

    /**
     * Append participant related acquisition field fillouts
     *
     * @param Event $event  Related event
     * @param array $config Related config for all participant related fillouts
     */
    private function appendParticipantAcquisitionColumns(Event $event, array $config)
    {
        /** @var AcquisitionAttribute $attribute */
        foreach ($event->getAcquisitionAttributes(false, true) as $attribute) {
            $this->appendAcquisitionColumn('participant', $attribute, $config);
        }
    }

    /**
     * Append participation related acquisition field fillouts
     *
     * @param Event $event  Related event
     * @param array $config Related config for all participation related fillouts
     */
    private function appendParticipationAcquisitionColumns(Event $event, array $config)
    {
        /** @var AcquisitionAttribute $attribute */
        foreach ($event->getAcquisitionAttributes(true, false) as $attribute) {
            $this->appendAcquisitionColumn('participation', $attribute, $config);
        }
    }

    /**
     * Append transmitted acquisition attribute column
     *
     * @param string               $group     Either participant or participation
     * @param AcquisitionAttribute $attribute Related attribute entity
     * @param array                $config    Column config
     */
    private function appendAcquisitionColumn(
        string $group,
        AcquisitionAttribute $attribute,
        array $config
    ) {
        $bid = 'acq_field_' . $attribute->getBid();
        if (!isset($config[$bid]) || !self::issetAndTrue($config[$bid], 'enabled')) {
            return;
        }
        switch($group) {
            case 'participant':
                $class = AcquisitionAttributeColumn::class;
                break;
            case 'participation':
                $class = ParticipationAcquisitionAttributeColumn::class;
                break;
            default:
                throw new \InvalidArgumentException('Unknown acquisition field appended');
        }
        switch ($config[$bid]['display']) {
            case \AppBundle\Export\Customized\Configuration::OPTION_SEPARATE_COLUMNS:
                if ($attribute->getFieldType() === \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class) {
                    $options = $attribute->getFieldTypeChoiceOptions(true);
                    foreach ($options as $optionLabel => $optionKey) {
                        $converter = function (AcquisitionAttributeFillout $fillout) use ($optionKey) {
                            $selectedOptions = $fillout->getValue();
                            if ((is_array($selectedOptions) && in_array($optionKey, $selectedOptions))
                                || $selectedOptions === $optionKey
                            ) {
                                return 'x';
                            } else {
                                return '';
                            }
                        };
                        /** @var AcquisitionAttributeColumn $column */
                        $column = new $class(
                            $group . '_' . $bid . '_' . $optionKey,
                            $optionLabel . ' (' . $attribute->getManagementTitle() . ')',
                            $attribute
                        );
                        $column->addHeaderStyleCallback(function($style){
                            /** @var \PHPExcel_Style $style */
                            $style->getAlignment()->setTextRotation(45);
                        });
                        $column->setWidth(4);
                        $column->setConverter($converter);
                        $this->addColumn($column);
                    }
                }
                break;
            default:
                /** @var AcquisitionAttributeColumn $column */
                $column = new $class(
                    $group . '_' . $bid, $attribute->getManagementTitle(), $attribute
                );
                $this->addColumn($column);
                break;
        }
    }

    /**
     * Determine if transmitted property exists as element in configuration array and is true
     *
     * @param array  $config Array to check
     * @param string $property
     * @return bool
     */
    protected static function issetAndTrue(array $config, $property)
    {
        return (isset($config[$property]) && $config[$property]);
    }

}