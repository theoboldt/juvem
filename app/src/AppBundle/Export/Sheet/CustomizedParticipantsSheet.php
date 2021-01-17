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
use AppBundle\Entity\AcquisitionAttribute\ChoiceFilloutValue;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Export\AttributeOptionExplanation;
use AppBundle\Export\Customized\Configuration;
use AppBundle\Export\Sheet\Column\AcquisitionAttributeAttributeColumn;
use AppBundle\Export\Sheet\Column\CallableAccessingColumn;
use AppBundle\Export\Sheet\Column\EntityAttributeColumn;
use AppBundle\Export\Sheet\Column\EntityPhoneNumberSheetAttributeColumn;
use AppBundle\Export\Sheet\Column\ParticipationAcquisitionAttributeColumn;
use AppBundle\Form\GroupType;
use AppBundle\Manager\Payment\PaymentManager;
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
     * @param Worksheet           $sheet        The excel workbook to use
     * @param Event               $event        Event to export
     * @param array               $participants List of participants qualified for export
     * @param array               $config       Configuration definition for export, validated
     *                                          via @see \AppBundle\Export\Customized\Configuration
     * @param PaymentManager|null $paymentManager
     */
    public function __construct(Worksheet $sheet, Event $event, array $participants, array $config, PaymentManager $paymentManager = null)
    {
        $this->event        = $event;
        $this->participants = $participants;
        $configParticipant  = $config['participant'];

        $groupBy = null;
        if (isset($configParticipant['grouping_sorting']['grouping']['enabled']) &&
            isset($configParticipant['grouping_sorting']['grouping']['field'])) {
            $groupBy = $configParticipant['grouping_sorting']['grouping']['field'];
        }
        parent::__construct($sheet, $groupBy, $paymentManager);

        if (self::issetAndTrue($configParticipant, 'aid')) {
            $column = new EntityAttributeColumn('aid', 'AID');
            $column->setWidth(4);
            $this->addColumn($column);
        }

        if (self::issetAndTrue($config['participation'], 'pid')) {
            $column = new EntityAttributeColumn('participation', 'PID');
            $column->setConverter(
                function (Participation $value) {
                    return $value->getPid();
                }
            );
            $column->setWidth(4);
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'nameFirst')) {
            $this->addColumn(new EntityAttributeColumn('nameFirst', 'Vorname'));
        }
        if (self::issetAndTrue($configParticipant, 'nameLast')) {
            $this->addColumn(new EntityAttributeColumn('nameLast', 'Nachname'));
        }

        if (self::issetAndTrue($configParticipant, 'birthday')) {
            $column = new EntityAttributeColumn('birthday', 'Geburtstag');
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
            $column = new EntityAttributeColumn('ageAtEvent', 'Alter');
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
            $column = EntityAttributeColumn::createSmallColumn('gender', 'Geschlecht');
            $column->setConverter(
                function ($value, Participant $entity) {
                    switch ($value) {
                        case Participant::LABEL_GENDER_FEMALE:
                        case Participant::LABEL_GENDER_FEMALE_ALIKE:
                            return 'w';
                        case Participant::LABEL_GENDER_MALE:
                        case Participant::LABEL_GENDER_MALE_ALIKE:
                            return 'm';
                        case Participant::LABEL_GENDER_OTHER:
                            return 'o';
                        case Participant::LABEL_GENDER_DIVERSE:
                            return 'd';
                        default:
                            return $value;
                    }
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'foodVegetarian')) {
            $column = EntityAttributeColumn::createYesNoColumn('food_vegetarian', 'Vegetarisch', 'food');
            $column->setConverter(
                function ($value, Participant $entity) {
                    $mask = $entity->getFood(true);
                    return $mask->has(ParticipantFood::TYPE_FOOD_VEGETARIAN) ? 'vs' : 'nein';
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'foodLactoseFree')) {
            $column = EntityAttributeColumn::createYesNoColumn('food_lactose_free', 'Laktosefrei', 'food');
            $column->setConverter(
                function ($value, Participant $entity) {
                    $mask = $entity->getFood(true);
                    return $mask->has(ParticipantFood::TYPE_FOOD_LACTOSE_FREE) ? 'lf' : 'nein';
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'foodLactoseNoPork')) {
            $column = EntityAttributeColumn::createYesNoColumn('food_lactose_no_pork', 'Ohne Schwein', 'food');
            $column->setConverter(
                function ($value, Participant $entity) {
                    $mask = $entity->getFood(true);
                    return $mask->has(ParticipantFood::TYPE_FOOD_NO_PORK) ? 'os' : 'nein';
                }
            );
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'infoMedical')) {
            $column = new EntityAttributeColumn('infoMedical', 'Medizinische Hinweise');
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
            $column = new EntityAttributeColumn('infoGeneral', 'Allgemeine Hinweise');
            $column->addDataStyleCalback(
                function ($style) {
                    /** @var Style $style */
                    $style->getAlignment()->setWrapText(true);
                }
            );
            $column->setWidth(35);
            $this->addColumn($column);
        }

        if (self::issetAndTrue($configParticipant, 'basePrice') && $event->getPrice()) {
            $column = new EntityAttributeColumn('basePrice', 'Grundpreis');
            $column->setConverter(
                function ($value, Participant $entity) {
                    return $entity->getBasePrice(true);
                }
            );
            $column->setNumberFormat('#,##0.00 €');
            $column->setWidth(8);
            $this->addColumn($column);
        }

        if ($event->getPrice() && $this->paymentManager) {
            if (self::issetAndTrue($configParticipant, 'price')) {
                $column = new CallableAccessingColumn(
                    'price', 'Preis', function (Participant $entity) {
                    return $this->paymentManager->getPriceForParticipant($entity, true);
                });
                $column->setNumberFormat('#,##0.00 €');
                $column->setWidth(8);
                $this->addColumn($column);
            }
            if (self::issetAndTrue($configParticipant, 'toPay')) {
                $column = new CallableAccessingColumn(
                    'toPay', 'Zu zahlen', function (Participant $entity) {
                    return $this->paymentManager->getToPayValueForParticipant($entity, true);
                });
                $column->setNumberFormat('#,##0.00 €');
                $column->setWidth(8);
                $this->addColumn($column);
            }
        }

        if (self::issetAndTrue($configParticipant, 'createdAt')) {
            $column = new EntityAttributeColumn('createdAt', 'Eingang Anmeldung');
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
            $column = new EntityAttributeColumn('participation_salutation', 'Anrede (Eltern)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getSalutation();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'nameFirst')) {
            $column = new EntityAttributeColumn('participation_nameFirst', 'Vorname (Eltern)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getNameFirst();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'nameLast')) {
            $column = new EntityAttributeColumn('participation_nameLast', 'Nachname (Eltern)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getNameLast();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'addressStreet')) {
            $column = new EntityAttributeColumn('participation_addressStreet', 'Straße (Anschrift)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getAddressStreet();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'addressCity')) {
            $column = new EntityAttributeColumn('participation_addressCity', 'Stadt (Anschrift)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getAddressCity();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'addressZip')) {
            $column = new EntityAttributeColumn('participation_addressZip', 'PLZ (Anschrift)', 'participation');
            $column->setConverter(
                function (Participation $value, $entity) {
                    return $value->getAddressZip();
                }
            );
            $this->addColumn($column);
        }
        if (self::issetAndTrue($configParticipation, 'email')) {
            $column = new EntityAttributeColumn('participation_email', 'E-Mail', 'participation');
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

            $column = EntityPhoneNumberSheetAttributeColumn::createCommaSeparated(
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
                $class = AcquisitionAttributeAttributeColumn::class;
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

        if ($attribute->getFieldType() === ChoiceType::class
            || $attribute->getFieldType() === GroupType::class
        ) {
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
                if ($attribute->getFieldType() === ChoiceType::class
                    || $attribute->getFieldType() === GroupType::class
                ) {
                    $choices = $attribute->getChoiceOptions();
                    /** @var AttributeChoiceOption $choice */
                    foreach ($choices as $choice) {
                        $optionKey = $choice->getId();

                        $optionLabel = $this->fetchChoiceOptionLabel($choice, $configOptionValue);
                        $converter   = function (Fillout $fillout = null) use ($choice, $configOptionValue, $explanation) {
                            if ($fillout) {
                                /** @var ChoiceFilloutValue $value */
                                $value = $fillout->getValue();
                                if ($value->getSelectedChoices()) {
                                    foreach ($value->getSelectedChoices() as $selectedChoice) {
                                        if ($choice->getId() === $selectedChoice->getId()) {
                                            if ($explanation) {
                                                $explanation->register($choice);
                                            }
                                            return 'x';
                                        }
                                    }
                                }

                            }
                            return '';
                        };
                        /** @var AcquisitionAttributeAttributeColumn $column */
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
                /** @var AcquisitionAttributeAttributeColumn $column */
                $column = new $class(
                    $group . '_' . $bid, $attribute->getManagementTitle(), $attribute
                );

                if ($attribute->getFieldType() === ChoiceType::class
                    || $attribute->getFieldType() === GroupType::class
                ) {
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
     * @param AcquisitionAttributeAttributeColumn $column Column to modify
     * @param int|null                   $width           If not null, transmitted width will be set
     * @param callable|null              $converter       Converter function if should be set
     */
    private function rotadedColumnHeader(
        AcquisitionAttributeAttributeColumn $column,
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
