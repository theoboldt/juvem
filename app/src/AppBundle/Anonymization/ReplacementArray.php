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
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'array';
    }

    
    /**
     * Replace array values
     *
     * @param array $list
     * @return array
     */
    private static function replaceArrayValues(array $list): array
    {
        $faker = \Faker\Factory::create();
        foreach ($list as $key => &$value) {
            if (is_array($value)) {
                $value = self::replaceArrayValues($value);
            } elseif (!is_numeric($value) && $value !== null) {
                $strlen = mb_strlen($value);
                if ($strlen < 5) {
                    $strlen = 5;
                }
                $value = $faker->text($strlen);
            }

        }
        return $list;
    }
    
    /**
     * @return string
     */
    public function provideReplacement()
    {
        return json_encode(self::replaceArrayValues($this->getOriginal()));
    }
}
