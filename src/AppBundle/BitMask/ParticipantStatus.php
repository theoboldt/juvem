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
}