<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Feedback;

interface FeedbackQuestionInterface
{

    /**
     * @return array
     */
    public function jsonSerialize();

    /**
     * @return string
     */
    public function getUuid(): string;

    /**
     * @return string
     */
    public function getInternalTitle(): string;

    /**
     * @return string
     */
    public function getQuestionText(): string;

    /**
     * @return int
     */
    public function getQuestionType(): int;
}
