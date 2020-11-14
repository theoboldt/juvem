<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export\Sheet\Column;


use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;

class EntityPhoneNumberSheetAttributeColumn extends EntityAttributeColumn
{
    
    /**
     * Create a new comma-separated phone number list
     *
     * @param string      $identifier         Identifier for document
     * @param string      $title              Title text for column
     * @param string|null $dataAttribute      Name of attribute from witch the data has to be fetched if
     *                                        differing from $identifier
     * @param bool        $includeDescription Set to true to include phone number description in export
     * @param bool        $wrap               If set to true, wrap text is activated and each entity ended by new line
     * @return EntityPhoneNumberSheetAttributeColumn
     */
    public static function createCommaSeparated(
        $identifier,
        $title,
        $dataAttribute = null,
        bool $includeDescription = false,
        bool $wrap = false
    ) {
        $column          = new self($identifier, $title, $dataAttribute);
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $column->setConverter(
            function ($value, $entity) use ($phoneNumberUtil, $includeDescription, $wrap) {
                if ($entity instanceof Participant) {
                    $entity = $entity->getParticipation();
                }
                $value = $entity->getPhoneNumbers();
                
                $numberTexts  = [];
                
                /** @var PhoneNumber $number */
                foreach ($value as $number) {
                    $numberText = $phoneNumberUtil->formatOutOfCountryCallingNumber($number->getNumber(), 'DE');
                    if ($includeDescription
                        && $number->getDescription()
                        && $number->getDescription() !== preg_replace('/\s+/', '', $numberText)
                    ) {
                        $numberText .= ' (';
                        $numberText .= $number->getDescription();
                        $numberText .= ')';
                    }
                    $numberTexts[] = $numberText;
                }
                
                $glue = $wrap ? ", \n" : ', ';
                
                return implode($glue, $numberTexts);
            }
        );
        $column->setNumberFormat(NumberFormat::FORMAT_TEXT);
        $column->setWidth(13.5);
        if ($wrap) {
            $column->addDataStyleCalback(
                function ($style) {
                    /** @var Style $style */
                    $style->getAlignment()->setWrapText(true);
                }
            );
        }
        
        return $column;
    }

}
