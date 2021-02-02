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


class ReplacementDateTime extends ReplacementQualified implements ReplacementInterface
{
    private array $details;

    /**
     * ReplacementDate constructor.
     *
     * @param       $original
     * @param array $details
     */
    public function __construct($original, array $details)
    {
        $this->details = $details;
        parent::__construct($original);
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'datetime';
    }

    /**
     * @return string
     */
    public function provideReplacement()
    {
        $int = mt_rand(1262304000, 1293753600);

        $date = $this->details['year'] . date("-m-d h:i:s", $int);
        return $date;
    }
}
