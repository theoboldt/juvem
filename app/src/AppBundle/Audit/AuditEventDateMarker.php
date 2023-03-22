<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Audit;

class AuditEventDateMarker implements AuditEventInterface
{

    const TYPE_DATE_MARKER = 'date_marker';

    /**
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $occurrenceDate;

    /**
     * @param \DateTimeImmutable $occurrenceDate
     */
    public function __construct(\DateTimeImmutable $occurrenceDate)
    {
        $this->occurrenceDate = $occurrenceDate;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_DATE_MARKER;
    }


    /**
     * @return \DateTimeImmutable
     */
    public function getOccurrenceDate(): \DateTimeImmutable
    {
        return $this->occurrenceDate;
    }

}
