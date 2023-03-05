<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Anonymization;


use AppBundle\Entity\CustomField\CustomFieldValueCollection;
use AppBundle\Entity\Participant;
use AppBundle\Form\BankAccountType;
use AppBundle\Form\GroupType;
use AppBundle\Form\ParticipantDetectingType;
use Ifsnop\Mysqldump\Mysqldump;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class DumpAnonymizer
{
    /**
     * @var Mysqldump
     */
    private $dump;
    
    /**
     * @var callable|null
     */
    private $tableProgressCallable = null;
    
    /**
     * @var array|string[][]
     */
    private $replacements = [];

    /**
     * @var array 
     */
    private $acquisitionAttributes = [];
    
    /**
     * DumpAnonymizer constructor.
     *
     * @param Mysqldump $dump
     */
    public function __construct(Mysqldump $dump)
    {
        $this->dump = $dump;
    }
    
    public function __invoke()
    {
        $this->dump->setTransformTableRowHook(
            function ($tableName, array $row) {
                $jsonColumns = [
                    'field_options',
                    'attribute_changes',
                    'collection_changes',
                ];
                foreach ($row as $columnName => $cell) {
                    if (in_array($columnName, $jsonColumns)
                        || ($tableName === 'weather_current' && $columnName === 'details')
                        || ($tableName === 'weather_forecast' && $columnName === 'details')
                    ) {
                        $cell = json_decode($cell, true);
                    }
                    $cell = $this->replace($tableName, $columnName, $cell, $row);
                    if (is_array($cell)) {
                        $cell = json_encode($cell);
                    }
                    $row[$columnName] = $cell;
                    
                    if ($tableName === 'user') {
                        $row['salt']     = '';
                        $row['password'] = '';
                    }
                }
                if ($tableName === 'acquisition_attribute') {
                    $this->acquisitionAttributes[$row['bid']] = $row;
                }
                /*
                if ($tableName !== $this->lastTable) {
                    $this->lastTable = $tableName;
                    dump($tableName);
                    dump($row);
                }
                */
                return $row;
            }
        );
    }
    
    /**
     * Detect gender
     *
     * @param array $row
     * @return string|null
     */
    private function detectGender(array $row): ?string
    {
        if (isset($row['gender'])) {
            switch ($row['gender']) {
                case Participant::LABEL_GENDER_MALE:
                case Participant::LABEL_GENDER_MALE_ALIKE:
                    return Participant::LABEL_GENDER_MALE;
                case Participant::LABEL_GENDER_FEMALE:
                case Participant::LABEL_GENDER_FEMALE_ALIKE:
                    return Participant::LABEL_GENDER_FEMALE;
                default:
                    return null;
            }
        }
        if (isset($row['salution'])) {
            switch (isset($row['salution'])) {
                case 'Herr':
                    return Participant::LABEL_GENDER_MALE;
                case 'Frau':
                    return Participant::LABEL_GENDER_FEMALE_ALIKE;
                default:
                    return null;
            }
        }
        if (isset($row['salutation'])) {
            switch (isset($row['salutation'])) {
                case 'Herr':
                    return Participant::LABEL_GENDER_MALE;
                case 'Frau':
                    return Participant::LABEL_GENDER_FEMALE_ALIKE;
                default:
                    return null;
            }
        }
        return null;
    }
    
    /**
     * Detect datum type and provide replacement qualified
     *
     * @param string $table       Table this value is related to
     * @param string $column      Column this value is related to
     * @param scalar|array $value Actual value or decoded json
     * @param array $row          Whole row data as well
     * @todo Prepare for custom Fields
     * @return ReplacementInterface|null
     */
    private function detectDatumType(string $table, string $column, $value, array $row): ?ReplacementInterface
    {
        switch ($table) {
            case 'migration_versions':
            case 'recipe':
            case 'recipe_feedback':
            case 'recipe_ingredient':
            case 'viand':
            case 'quantity_unit':
            case 'task':
                return null;
        }
        if (($table === 'user' && ($column === 'password' || $column === 'salt' || $column === 'roles'))
            || ($table === 'weather_current' && $column === 'provider')
            || $column === 'salution'
            || $column === 'salutation'
        ) {
            return null;
        }
        if (($table === 'location_description' && $column === 'details')
            || ($table === 'user' && ($column === 'settings' || $column === 'settings_hash'))
            || ($table === 'weather_current' && $column === 'details')
        ) {
            return null;
        }
        $keepColumns = [
            'gender',
            'field_type',
            'field_options',
            'price_formula',
            'related_class',
            'related_id',
            'operation',
            'age_range',
            'image_filename',
            'management_title',
            'form_title',
        ];
        $keepTables = [
            'food_property',
        ];
        if ($column === 'custom_field_values') {
            return null;
            
            if ($value) {
                $valueDecoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return null;             
                }
            } else {
                $valueDecoded = [];
            }
            $customFieldValueCollection = CustomFieldValueCollection::createFromArray($valueDecoded);
            $attribute = $this->acquisitionAttributes[$row['bid']] ?? null;
        
            switch ($attribute['field_type']) {
                case BankAccountType::class:
                    return new FakerReplacement(
                        $value,
                        $name,
                        function (\Faker\Generator $faker) use ($value, $row) {
                            return [
                                'bankAccountBic'   => $faker->swiftBicNumber,
                                'bankAccountIban'  => $faker->iban('DE'),
                                'bankAccountOwner' => $faker->lastName,
                            ];
                        }
                    );
                case DateType::class:
                    return new ReplacementDate($value);
                case DateTimeType::class:
                    return new ReplacementDateTime($value);
                case ParticipantDetectingType::class:
                    $value                = json_decode($value, true);
                    $valueFirstName       = $value['participantDetectingFirstName'] ?? '';
                    $replacementFirstName = new FakerReplacement(
                        $valueFirstName,
                        'name_first',
                        function (\Faker\Generator $faker) use ($valueFirstName) {
                            if ($valueFirstName === null) {
                                return null;
                            }
                            return $faker->firstName;
                        }
                    );
                    $valueLastName        = $value['participantDetectingLastName'] ?? '';
                    $replacementLastName  = new FakerReplacement(
                        $valueLastName,
                        'name_last',
                        function (\Faker\Generator $faker) use ($valueLastName) {
                            if ($valueLastName === null) {
                                return null;
                            }
                            return $faker->firstName;
                        }
                    );
                    return new FakerReplacement(
                        $value,
                        $name,
                        function (\Faker\Generator $faker) use (
                            $value, $row, $replacementFirstName, $replacementLastName
                        ) {
                            $result = [
                                'participantDetectingFirstName'   => $this->provideReplacement($replacementFirstName),
                                'participantDetectingLastName'    => $this->provideReplacement($replacementLastName),
                                'participant_selected_first_name' => $this->provideReplacement($replacementFirstName),
                                'participant_selected_last_name'  => $this->provideReplacement($replacementLastName),
                            ];
                            if (array_key_exists('participant_selected', $value)) {
                                $result['participant_selected'] = $value['participant_selected'];
                            }
                            if (array_key_exists('system_selection', $value)) {
                                $result['system_selection'] = $value['system_selection'];
                            }
                            return $result;
                        }
                    );
                case NumberType::class:
                    return new FakerReplacement(
                        $value,
                        $name,
                        function (\Faker\Generator $faker) use ($value) {
                            $valueMin = round($value*0.5);
                            $valueMax = round($value*1.5);
                            return $faker->numberBetween($valueMin, $valueMax);
                        }
                    );
                case GroupType::class;
                case ChoiceType::class:
                    return null;
                default:
                    if ($value === null) {
                        return null;
                    }
                    if (is_numeric($value)) {
                        return new FakerReplacement(
                            $value,
                            $name,
                            function (\Faker\Generator $faker) use ($value) {
                                $valueMin = round($value*0.5);
                                $valueMax = round($value*1.5);
                                return $faker->numberBetween($valueMin, $valueMax);
                            }
                        );
                    }
                    return new ReplacementArray($value, $name);
            }
        }
        
        if ($table === 'phone_number' && $column === 'number') {
            return new FakerReplacement(
                $value,
                $table,
                function (\Faker\Generator $faker) {
                    return '+49' . substr($faker->e164PhoneNumber, 3);
                }
            );
        }
        if (is_numeric($value)
            && ($column === 'latitude' || $column === 'longitude')
        ) {
            return new FakerReplacement(
                $value,
                $column,
                function (\Faker\Generator $faker) use ($column) {
                    if ($column === 'latitude') {
                        return $faker->latitude;
                    } else {
                        return $faker->longitude;
                    }
                }
            );
        }
        if (is_numeric($value)
            && ($column === 'osm_id')
        ) {
            return new FakerReplacement(
                $value,
                $column,
                function (\Faker\Generator $faker) {
                    return $faker->numberBetween(0, 9999);
                }
            );
        }
        if (is_numeric($value)
            || $value === null
            || $value === '[]'
            || in_array($column, $keepColumns)
            || in_array($table, $keepTables)
        ) {
            return null;
        }
        if (($table === 'export_template' && $column === 'configuration')
            || ($table === 'type' && $column === 'type')
        ) {
            return null;
        }
        if (is_string($value)) {
            $valueStrlen = mb_strlen($value);
            switch ($column) {
                case 'name_last':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->lastName;
                        }
                    );
                case 'name_first':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) use ($row) {
                            if ($this->detectGender($row) === Participant::LABEL_GENDER_MALE) {
                                return $faker->firstNameMale;
                            } elseif ($this->detectGender($row) === Participant::LABEL_GENDER_FEMALE) {
                                return $faker->firstNameFemale;
                            } else {
                                return $faker->firstName;
                            }
                        }
                    );
                case 'address_street_name':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->streetName;
                        }
                    );
                case 'address_street_number':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->buildingNumber;
                        }
                    );
                case 'address_street':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->streetName . ', ' . $faker->buildingNumber;
                        }
                    );
                case 'address_city':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->city;
                        }
                    );
                case 'address_zip':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->postcode;
                        }
                    );
                case 'address_country':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->country;
                        }
                    );
                case 'username':
                case 'username_canonical':
                case 'email':
                case 'email_canonical':
                    return new FakerReplacement(
                        $value,
                        'email',
                        function (\Faker\Generator $faker) {
                            return $faker->email;
                        }
                    );
                case 'link_url':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->url;
                        }
                    );
                case 'confirmation_token':
                case 'disable_token':
                    return new FakerReplacement(
                        $value,
                        $column,
                        function (\Faker\Generator $faker) {
                            return $faker->uuid;
                        }
                    );
            }
            if (preg_match('/^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})$/i', $value, $results)) {
                return new ReplacementDate(
                    $value
                );
            } elseif (preg_match(
                '/^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2}) (?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})$/i',
                $value, $results
            )) {
                return new ReplacementDateTime(
                    $value
                );
            } elseif (preg_match(
                '/^(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})$/i',
                $value, $results
            )) {
                return new FakerReplacement(
                    $value,
                    'time',
                    function (\Faker\Generator $faker) {
                        return $faker->time();
                    }
                );
            }
            if ($valueStrlen > 2) {
                return new FakerReplacement(
                    $value,
                    'text',
                    function (\Faker\Generator $faker) use ($valueStrlen) {
                        if ($valueStrlen < 5) {
                            $valueStrlen = 5;
                        }
                        return $faker->text($valueStrlen);
                    }
                );
            }
        } elseif (is_array($value)) {
            $replaceValueColumns = [
                'attribute_changes',
                'collection_changes',
                'details'
            ];
            if (in_array($column, $replaceValueColumns)) {
                return new ReplacementArray($value);
            }
        }
        return null;
    }
    
    /**
     * Replace value
     *
     * @param string $table       Table this value is related to
     * @param string $column      Column this value is related to
     * @param scalar|array $value Actual value or decoded json
     * @param array $row          Whole row data as well
     * @return bool|float|int|mixed|string|null
     */
    private function replace(string $table, string $column, $value, array $row)
    {
        $qualified = $this->detectDatumType($table, $column, $value, $row);
        if ($qualified) {
            return $this->provideReplacement($qualified);
        }
        
        return $value;
    }
    
    /**
     * Provide replacement
     *
     * @param ReplacementInterface $qualified
     * @return mixed|string
     */
    private function provideReplacement(ReplacementInterface $qualified) {
        if (!isset($this->replacements[$qualified->getType()][$qualified->getKey()])) {
            $this->replacements[$qualified->getType()][$qualified->getKey()] = $qualified->provideReplacement();
        }
        $value = $this->replacements[$qualified->getType()][$qualified->getKey()];
        
        return $value;
    }
    
}
