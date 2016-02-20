<?php

namespace AppBundle\BitMask;

class ParticipantStatus extends BitMaskAbstract
{

    const TYPE_STATUS_CONFIRMED = 1;
    const TYPE_STATUS_PAID      = 2;
    const TYPE_STATUS_WITHDRAWN = 4;

    const LABEL_STATUS_CONFIRMED   = 'bestätigt';
    const LABEL_STATUS_PAID        = 'bezahlt';
    const LABEL_STATUS_WITHDRAWN   = 'zurückgezogen';

    const LABEL_STATUS_UNCONFIRMED = 'unbestätigt';

    /**
     * Contains the options the bitmask is able to store as array
     *
     * @var array
     */
    protected $options
        = array(
            self::TYPE_STATUS_CONFIRMED,
            self::TYPE_STATUS_PAID,
            self::TYPE_STATUS_WITHDRAWN
        );

    /**
     * Contains the labels of every bitmask option
     *
     * @var array
     */
    protected $labels
        = array(
            self::TYPE_STATUS_CONFIRMED => self::LABEL_STATUS_CONFIRMED,
            self::TYPE_STATUS_PAID      => self::LABEL_STATUS_PAID,
            self::TYPE_STATUS_WITHDRAWN => self::LABEL_STATUS_WITHDRAWN
        );
}