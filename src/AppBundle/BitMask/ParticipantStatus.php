<?php

namespace AppBundle\BitMask;

class ParticipantStatus extends BitMaskAbstract
{
    const TYPE_STATUS_CONFIRMED          = 1;
    const TYPE_STATUS_PAID               = 2;
    const TYPE_STATUS_WITHDRAWN          = 4;
    const TYPE_STATUS_WITHDRAW_REQUESTED = 8;

    const LABEL_STATUS_CONFIRMED          = 'bestätigt';
    const LABEL_STATUS_PAID               = 'bezahlt';
    const LABEL_STATUS_WITHDRAWN          = 'zurückgezogen';
    const LABEL_STATUS_WITHDRAW_REQUESTED = 'Rücknahme angefragt';

    const LABEL_STATUS_UNCONFIRMED = 'unbestätigt';

    /**
     * Create formatter configured for default usage of @see ParticipantStatus
     *
     * @return LabelFormatter
     */
    public static function formatter()
    {
        $formatter = new LabelFormatter();
        $formatter->addAbsenceLabel(self::TYPE_STATUS_CONFIRMED, self::LABEL_STATUS_UNCONFIRMED);
        $formatter->addCustomType(self::TYPE_STATUS_CONFIRMED, 'success');
        $formatter->addCustomType(self::TYPE_STATUS_WITHDRAW_REQUESTED, 'warning');
        $formatter->addCustomType(self::TYPE_STATUS_WITHDRAWN, 'danger');
        $formatter->addCustomType(self::TYPE_STATUS_PAID, 'info');

        return $formatter;
    }
}