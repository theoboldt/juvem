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


interface ReplacementInterface
{

    /**
     * @return string
     */
    public function getOriginal();

    /**
     * Get replacement key
     * 
     * @return string
     */
    public function getKey(): string;

    /**
     * Get replacement type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Provide replacement for this value
     *
     * @return scalar
     */
    public function provideReplacement();
}
