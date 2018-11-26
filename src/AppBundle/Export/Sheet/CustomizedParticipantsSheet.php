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
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Export\AttributeOptionExplanation;
use AppBundle\Export\Customized\Configuration;
use AppBundle\Export\Sheet\Column\AcquisitionAttributeColumn;
use AppBundle\Export\Sheet\Column\EntityColumn;
use AppBundle\Export\Sheet\Column\EntityPhoneNumberSheetColumn;
use AppBundle\Export\Sheet\Column\ParticipationAcquisitionAttributeColumn;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CustomizedParticipantsSheet extends ParticipantsSheetBase implements SheetRequiringExplanationInterface
{
    /**
     * Registered explanations
     *
     * @var array|AttributeOptionExplanation[]
     */
    private $explanations = [];

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
                return Date::FormattedPHPToExcel(
                        $value->format('Y'), $value->format('m'), $value->format('d')
                    );
                }
            );
            $column->setWidth(10);
            $this->addColumn($column);
        }

        if ($configParticipant['ageAtEvent'] != 'none') {
            $column = new EntityColumn('ageAtEvent', 'Alter');
            $column->setWidth(5);
            switch ($configParticipant['ageAtEvent']) {
                case 'round':
                    $column->setNumberFormat('0');
                    break;
                case 'completed':
                    $column->setConverter(
                        function ($value, Participant $entity) {
                            return $entity->getYearsOfLifeAtEvent();
                        }
                    );
                    $column->setNumberFormat('0');
                    $column->setWidth(4);
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
                    /** @var Style $style */
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
                    /** @var Style $style */
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
                return Date::FormattedPHPToExcel(
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

        if (self::issetAndTrue($configParticipation, 'salutation')) {
            $column = new EntityColumn('participation_salutation', 'Anrede (Eltern)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getSalutation();
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
            switch ($configParticipation['phoneNumber']) {
                case 'comma_description';
                case 'comma_description_wrap';
                    $phoneNumberIncludeDescription = true;
                    break;
                default:
                    $phoneNumberIncludeDescription = false;
                    break;
            }
            switch ($configParticipation['phoneNumber']) {
                case 'comma_wrap';
                case 'comma_description_wrap';
                    $wrapNumbers = true;
                    break;
                default:
                    $wrapNumbers = false;
                    break;
            }

            $column = EntityPhoneNumberSheetColumn::createCommaSeparated(
                'phoneNumbers',
                'Telefonnummern',
                'participation',
                $phoneNumberIncludeDescription,
                $wrapNumbers
            );

            if ($wrapNumbers) {
                $column->setWidth(50);
            } else {
                $column->setWidth(13.5);
            }
            $this->addColumn($column);
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
        /** @var \AppBundle\Entity\AcquisitionAttribute\Attribute $attribute */
        foreach ($event->getAcquisitionAttributes(false, true, false, true, true) as $attribute) {
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
        /** @var \AppBundle\Entity\AcquisitionAttribute\Attribute $attribute */
        foreach ($event->getAcquisitionAttributes(true, false, false, true, true) as $attribute) {
            $this->appendAcquisitionColumn('participation', $attribute, $config);
        }
    }

    /**
     * Append transmitted acquisition attribute column
     *
     * @param string               $group     Either participant or participation
     * @param \AppBundle\Entity\AcquisitionAttribute\Attribute $attribute Related attribute entity
     * @param array                $config    Column config
     */
    private function appendAcquisitionColumn(
        string $group,
        Attribute $attribute,
        array $config
    ) {
        $bid = 'acq_field_' . $attribute->getBid();
        if (!isset($config[$bid]) || !self::issetAndTrue($config[$bid], 'enabled')) {
            return;
        }
        switch ($group) {
            case 'participant':
                $class = AcquisitionAttributeColumn::class;
                break;
            case 'participation':
                $class = ParticipationAcquisitionAttributeColumn::class;
                break;
            default:
                throw new \InvalidArgumentException('Unknown acquisition field appended');
        }

        $configDisplay = Configuration::OPTION_DEFAULT;
        if (isset($config[$bid]['display'])) {
            $configDisplay = $config[$bid]['display'];
        }

        if ($attribute->getFieldType() === ChoiceType::class) {
            $configOptionValue = Configuration::OPTION_VALUE_SHORT;
        } else {
            $configOptionValue = Configuration::OPTION_VALUE_MANAGEMENT;
        }
        if (isset($config[$bid]['optionValue'])) {
            $configOptionValue = $config[$bid]['optionValue'];
        }
        if ($configOptionValue === Configuration::OPTION_VALUE_SHORT) {
            $explanation          = new AttributeOptionExplanation($attribute);
            $this->explanations[] = $explanation;
        } else {
            $explanation = null;
        }

        switch ($configDisplay) {
            case Configuration::OPTION_SEPARATE_COLUMNS:
                if ($attribute->getFieldType() === ChoiceType::class) {
                    $choices = $attribute->getChoiceOptions();
                    /** @var AttributeChoiceOption $choice */
                    foreach ($choices as $choice) {
                        $optionKey = $choice->getId();

                        $optionLabel = $this->fetchChoiceOptionLabel($choice, $configOptionValue);
                        $converter   = function (Fillout $fillout) use ($choice, $configOptionValue, $explanation) {
                            foreach ($fillout->getSelectedChoices() as $selectedChoice) {
                                if ($choice->getId() === $selectedChoice->getId()) {
                                    if ($explanation) {
                                        $explanation->register($choice);
                                    }

                                    return 'x';
                                }
                            }
                            return '';
                        };
                        /** @var AcquisitionAttributeColumn $column */
                        $column = new $class(
                            $group . '_' . $bid . '_' . $optionKey,
                            $optionLabel . ' (' . $attribute->getManagementTitle() . ')',
                            $attribute
                        );
                        $this->rotadedColumnHeader($column, 4, $converter);
                        $this->addColumn($column);
                    }
                }
                break;
            default:
                /** @var AcquisitionAttributeColumn $column */
                $column = new $class(
                    $group . '_' . $bid, $attribute->getManagementTitle(), $attribute
                );

                if ($attribute->getFieldType() === ChoiceType::class) {
                    $optionValue = $configOptionValue;
                    $converter   = function (Fillout $fillout = null) use ($optionValue, $explanation) {
                        $selectedOptions = [];

                        if ($fillout === null) {
                            return '';
                        }
                        
                        foreach ($fillout->getSelectedChoices() as $choice) {
                            if ($explanation) {
                                $explanation->register($choice);
                            }
                            switch ($optionValue) {
                                case Configuration::OPTION_VALUE_FORM:
                                    $selectedOptions[] = $choice->getFormTitle();
                                    break;
                                case Configuration::OPTION_VALUE_MANAGEMENT:
                                    $selectedOptions[] = $choice->getManagementTitle(true);
                                    break;
                                case Configuration::OPTION_VALUE_SHORT:
                                default:
                                    $selectedOptions[] = $choice->getShortTitle(true);
                                    break;
                            }
                        }

                        return implode(', ', $selectedOptions);
                    };
                    $column->setConverter($converter);
                }
                $this->addColumn($column);
                break;
        }
    }

    /**
     * Rotate column, update width accordingly, possibly configure converter
     *
     * @param AcquisitionAttributeColumn $column Column to modify
     * @param int|null                   $width If not null, transmitted width will be set
     * @param callable|null              $converter Converter function if should be set
     */
    private function rotadedColumnHeader(
        AcquisitionAttributeColumn $column,
        int $width = null,
        callable $converter = null
    ) {
        $column->addHeaderStyleCallback(
            function ($style) {
                /** @var Style $style */
                $style->getAlignment()->setTextRotation(45);
            }
        );
        if ($width !== null) {
            $column->setWidth($width);

        }
        if ($converter) {
            $column->setConverter($converter);
        }
    }

    /**
     * Fetch choice option label for choice depending on configuration
     *
     * @param AttributeChoiceOption $choice      Choice
     * @param string                $optionValue Selection for which label to use
     * @return string Label
     */
    private function fetchChoiceOptionLabel(AttributeChoiceOption $choice, string $optionValue)
    {
        switch ($optionValue) {
            case Configuration::OPTION_VALUE_FORM:
                return $choice->getFormTitle();
            case Configuration::OPTION_VALUE_MANAGEMENT:
                return $choice->getManagementTitle(true);
            case Configuration::OPTION_VALUE_SHORT:
            default:
                return $choice->getShortTitle(true);
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


    /**
     * Get  list of all attached @see AttributeOptionExplanation
     *
     * @return AttributeOptionExplanation[]|array
     */
    public function getExplanations(): array
    {
        return $this->explanations;
    }

}
