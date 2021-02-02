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


abstract class ReplacementQualified
{
    /**
     * @var scalar
     */
    private $original;

    /**
     * ReplacementQualified constructor.
     *
     * @param scalar $original
     */
    public function __construct($original)
    {
        $this->original = $original;
    }

    /**
     * @return mixed
     */
    public function getOriginal()
    {
        return $this->original;
    }

    public function getKey(): string
    {
        if (is_array($this->original)) {
            return sha1(json_encode($this->original));
        } else {
            return (string)$this->original;
        }
    }
}
