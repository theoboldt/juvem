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


use Ifsnop\Mysqldump\Mysqldump;

class DumpAnonymizer
{
    /**
     * @var Mysqldump
     */
    private $dump;

    /**
     * @var array|string[][]
     */
    private $replacements = [];

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
                ];
                foreach ($row as $columnName => $cell) {
                    if (in_array($columnName, $jsonColumns)) {
                        $cell = json_decode($cell, true);
                    }
                    $cell = $this->replace($tableName, $columnName, $cell);
                    if (is_array($cell)) {
                        $cell = json_encode($cell);
                    }
                    $row[$columnName] = $cell;

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
     * Detect datum type and provide replacement qualified
     *
     * @param string       $table
     * @param string       $column
     * @param scalar|array $value
     * @return ReplacementInterface|null
     */
    private function detectDatumType(string $table, string $column, $value): ?ReplacementInterface
    {
        $keepColumns = [
            'field_type',
            'related_class',
            'related_id',
            'operation',
            'age_range',
            'image_filename',
        ];
        $keepTables  = [
            'food_property',
        ];

        if ($table === 'phone_number' && $column === 'number') {
            return new FakerReplacement(
                $value,
                $table,
                function (\Faker\Generator $faker) {
                    return $faker->phoneNumber;
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
                        function (\Faker\Generator $faker) {
                            return $faker->firstName;
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
            }
            if (preg_match('/^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})$/i', $value, $results)) {
                return new ReplacementDate(
                    $value,
                    $results
                );
            } elseif (preg_match(
                '/^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2}) (?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})$/i',
                $value, $results
            )) {
                return new ReplacementDateTime(
                    $value,
                    $results
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
     * @param string       $table
     * @param string       $column
     * @param scalar|array $value
     * @return bool|float|int|mixed|string|null
     */
    private function replace(string $table, string $column, $value)
    {
        switch ($table) {
            case 'migration_versions':
            case 'recipe':
            case 'recipe_feedback';
            case 'recipe_ingredient':
            case 'viand':
            case 'quantity_unit':
            case 'task':
                return $value;
        }
        if (($table === 'user' && ($column === 'password' || $column === 'roles'))
            || ($table === 'weather_current' && $column === 'provider')
            || $column === 'salution'
        ) {
            return $value;
        }
        if (($table === 'location_description' && $column === 'details')
            || ($table === 'user' && ($column === 'settings' || $column === 'settings_hash'))
            || ($table === 'weather_current' && $column === 'details')
        ) {
            return [];
        }

        $qualified = $this->detectDatumType($table, $column, $value);
        if ($qualified) {
            if (!isset($this->replacements[$qualified->getType()][$qualified->getKey()])) {
                $this->replacements[$qualified->getType()][$qualified->getKey()] = $qualified->provideReplacement();
            }
            $value = $this->replacements[$qualified->getType()][$qualified->getKey()];
        }

        return $value;
    }

}
