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


use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use libphonenumber\PhoneNumberUtil;

class EntityPhoneNumberSheetColumn extends EntitySheetColumn
{

    /**
     * Create a new comma-separated phone number list
     *
     * @param string      $identifier         Identifier for document
     * @param string      $title              Title text for column
     * @param string|null $dataAttribute      Name of attribute from witch the data has to be fetched if
     *                                        differing from $identifier
     * @param bool        $includeDescription Set to true to include phone number description in export
     * @return EntityPhoneNumberSheetColumn
     */
    public static function createCommaSeparated($identifier, $title, $dataAttribute = null, $includeDescription = false)
    {
        $column          = new self($identifier, $title, $dataAttribute);
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $column->setConverter(
            function ($value, $entity) use ($phoneNumberUtil, $includeDescription) {
                if ($entity instanceof Participant) {
                    $entity = $entity->getParticipation();
                }
                $value = $entity->getPhoneNumbers();

                $numberText  = '';
                $numberCount = count($value);
                $i           = 1;

                /** @var PhoneNumber $number */
                foreach ($value as $number) {
                    $numberText .= $phoneNumberUtil->formatOutOfCountryCallingNumber($number->getNumber(), 'DE');
                    if ($includeDescription && $number->getDescription()) {
                        $numberText .= ' (';
                        $numberText .= $number->getDescription();
                        $numberText .= ')';
                    }

                    if ($i++ < $numberCount) {
                        $numberText .= ', ';
                    }
                }

                return $numberText;
            }
        );
        $column->setWidth(13.5);

        return $column;
    }

}