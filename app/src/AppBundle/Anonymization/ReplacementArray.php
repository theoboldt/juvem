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


class ReplacementArray extends ReplacementQualified implements ReplacementInterface
{
    /**
     * @var string
     */
    private string $type;
    
    /**
     * ReplacementArray constructor.
     *
     * @param scalar $original
     * @param string $type
     */
    public function __construct($original, string $type = 'array')
    {
        $this->type = $type;
        parent::__construct($original);
    }
    
    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Replace array values
     *
     * @param array $list
     * @return array
     */
    private static function replaceArrayValues(array $list): array
    {
        $faker = \Faker\Factory::create('de_DE');
        foreach ($list as $key => $value) {
            if (is_array($value)) {
                $list[$key] = self::replaceArrayValues($value);
            } elseif (is_numeric($value)) {
                $list[$key] = $faker->numberBetween(0, 99);
            } elseif (!is_numeric($value) && $value !== null) {
                $strlen = mb_strlen($value);
                if ($strlen < 5) {
                    $strlen = 5;
                }
                $list[$key] = $faker->text($strlen);
            }
            
        }
        return $list;
    }
    
    /**
     * @return string
     */
    public function provideReplacement()
    {
        $original = $this->getOriginal();
        if (is_array($original)) {
            return json_encode(self::replaceArrayValues($original));
        } else {
            $strlen = mb_strlen($original);
            if ($strlen < 5) {
                $strlen = 5;
            }
            $faker = \Faker\Factory::create('de_DE');
            return $faker->text($strlen);
        }
    }
}
