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


class FakerReplacement extends ReplacementQualified implements ReplacementInterface
{
    /**
     * @var string
     */
    private string $type;

    /**
     * @var callable
     */
    private $fakeGenerator;

    /**
     * FakerReplacement constructor.
     *
     * @param          $original
     * @param string   $type
     * @param callable $fakeGenerator
     */
    public function __construct($original, string $type, callable $fakeGenerator)
    {
        $this->type          = $type;
        $this->fakeGenerator = $fakeGenerator;
        parent::__construct($original);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function provideReplacement()
    {
        $faker = \Faker\Factory::create('de_DE');

        $generator   = $this->fakeGenerator;
        $replacement = $generator($faker);
        return $replacement;
    }


}
