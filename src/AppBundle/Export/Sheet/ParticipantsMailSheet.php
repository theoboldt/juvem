<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participation;
use AppBundle\Export\Sheet\Column\EntityColumn;
use libphonenumber\PhoneNumberUtil;

class ParticipantsMailSheet extends ParticipantsSheet
{

    public function __construct(\PHPExcel_Worksheet $sheet, Event $event, array $participants)
    {
        parent::__construct($sheet, $event, $participants);

        $column = new EntityColumn('nameFirstParticipation', 'Eltern Vorname', 'participation');
        $column->setConverter(
            function (Participation $value) {
                return $value->getNameFirst();
            }
        );
        $this->addColumn($column);

        $column = new EntityColumn('nameLastParticipation', 'Eltern Nachname', 'participation');
        $column->setConverter(
            function (Participation $value) {
                return $value->getNameLast();
            }
        );
        $this->addColumn($column);

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $column          = new EntityColumn('phoneNumbersParticipation', 'Eltern Telefonnummern', 'participation');
        $column->setConverter(
            function (Participation $value, $entity) use ($phoneNumberUtil) {
                $value = $value->getPhoneNumbers();

                $numberText  = '';
                $numberCount = count($value);
                $i           = 1;

                /** @var PhoneNumbUsinger $number */
                foreach ($value as $number) {
                    $numberText .= $phoneNumberUtil->formatOutOfCountryCallingNumber($number->getNumber(), 'DE');
                    if ($number->getDescription()) {
                        $numberText .= ' (';
                        $numberText .= $number->getDescription();
                        $numberText .= ')';
                    }

                    if ($i++ < $numberCount) {
                        $numberText .= "\n";
                    }
                }

                return $numberText;
            }
        );
        $this->addColumn($column);

    }
}