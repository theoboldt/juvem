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
        $original = $this->getOriginal();
        if ($original === null) {
            return null;
        }
        if (!preg_match(
            '/^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2}) (?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})$/i',
            $original, $details
        )) {
            throw new \InvalidArgumentException('Unknown format "' . $original . '" provided');
        }
        
        $int = mt_rand(1262304000, 1293753600);
        
        $date = $details['year'] . date("-m-d h:i:s", $int);
        return $date;
    }
}
