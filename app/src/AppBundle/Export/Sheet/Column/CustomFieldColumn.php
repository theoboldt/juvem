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


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\CustomFieldValueInterface;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\CustomField\NumberCustomFieldValue;
use AppBundle\Entity\CustomField\OptionProvidingCustomFieldValueInterface;
use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
use AppBundle\Export\AttributeOptionExplanation;
use AppBundle\Export\Customized\Configuration;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomFieldColumn extends EntityAttributeColumn
{

    /**
     * @see Attribute
     */
    protected Attribute $attribute;

    /**
     * Custom Field display configuration
     *
     * @var array
     */
    protected array $config;

    /**
     * Custom field selection option explanation if configured
     *
     * @var AttributeOptionExplanation|null
     */
    private ?AttributeOptionExplanation $explanation;

    /**
     * Create a new column
     *
     * @param string                          $identifier  Identifier for document
     * @param string                          $title       Title text for column
     * @param Attribute                       $attribute   Attribute object
     * @param array                           $config      Custom field Display configuration
     * @param AttributeOptionExplanation|null $explanation Custom field selection option explanation if configured
     */
    public function __construct(
        $identifier,
        $title,
        Attribute $attribute,
        array $config,
        ?AttributeOptionExplanation $explanation = null
    ) {
        $this->attribute   = $attribute;
        $this->config      = $config;
        $this->explanation = $explanation;
        parent::__construct($identifier, $title);

        if ($attribute->getCustomFieldValueType() === NumberCustomFieldValue::class) {
            $this->setNumberFormat(NumberFormat::FORMAT_NUMBER);
            $this->setDataType(DataType::TYPE_NUMERIC);
        } else {
            $this->setNumberFormat(NumberFormat::FORMAT_TEXT);
            $this->setDataType(DataType::TYPE_STRING);
        }
    }

    /**
     * Extract {@see CustomFieldValueContainer} from transmitted entity
     *
     * @param EntityHavingCustomFieldValueInterface $entity Entity
     * @return CustomFieldValueContainer
     */
    protected function extractCustomFieldValueContainer(
        EntityHavingCustomFieldValueInterface $entity
    ): CustomFieldValueContainer {
        if (!$entity instanceof EntityHavingCustomFieldValueInterface) {
            throw new \InvalidArgumentException(
                'Instance of ' . EntityHavingCustomFieldValueInterface::class . ' expected'
            );
        }
        return $entity->getCustomFieldValues()->getByCustomField($this->attribute);
    }


    /**
     * Get value by identifier of this column for transmitted entity
     *
     * @param EntityHavingCustomFieldValueInterface $entity Entity
     * @return  mixed
     */
    public function getData($entity)
    {
        return $this->extractCustomFieldValueContainer($entity);
    }
    
    
    /**
     * Apply {@see self::converter} to {@see getData()} value if configured
     *
     * @param mixed $value  Extracted value
     * @param mixed $entity Full entity passed to {@see process()}
     * @return mixed Converted value or original value
     */
    public function getConvertedData($value, $entity)
    {
        if ($this->hasConverter()) {
            return parent::getConvertedData($value, $entity);
        } else {
            /** @var CustomFieldValueInterface $customFieldValue */
            $customFieldValue = $value->getValue();

            if ($customFieldValue instanceof OptionProvidingCustomFieldValueInterface) {
                $selectedOptions = [];

                if (empty($customFieldValue->getSelectedChoices())) {
                    return '';
                }

                foreach ($customFieldValue->getSelectedChoices() as $choiceId) {
                    $choice = $this->attribute->getChoiceOption($choiceId);
                    if ($choice) {
                        if ($this->explanation) {
                            $this->explanation->register($choice);
                        }
                        switch ($this->config['optionValue'] ?? null) {
                            case Configuration::OPTION_VALUE_FORM:
                                $selectedOptions[] = $choice->getFormTitle();
                                break;
                            case Configuration::OPTION_VALUE_MANAGEMENT:
                                $selectedOptions[] = $choice->getManagementTitle(true);
                                break;
                            case Configuration::OPTION_VALUE_SHORT:
                            default:
                                $selectedOptions[] = $choice->getShortTitle(true);
                                break;
                        }
                    } else {
                        //unknown option
                        $selectedOptions[] = $choiceId;
                    }
                }
                if (!isset($this->config['optionComment'])
                    || $this->config['optionComment'] === Configuration::OPTION_COMMENT_NEWLINE) {
                    //this is impossible for this field type, so changing to comment instead
                    $this->config['optionComment'] = Configuration::OPTION_COMMENT_COMMENT;
                }

                return implode(', ', $selectedOptions);
            } elseif ($customFieldValue instanceof ParticipantDetectingCustomFieldValue) {
                if ($customFieldValue->getParticipantFirstName() && $customFieldValue->getParticipantLastName()) {
                    return $customFieldValue->getParticipantFirstName() . ' ' .
                           $customFieldValue->getParticipantLastName();
                } elseif (empty($customFieldValue->getRelatedFirstName()) &&
                          empty($customFieldValue->getRelatedLastName())) {
                    return '';
                } else {
                    return $customFieldValue->getRelatedFirstName() . ' ' . $customFieldValue->getRelatedLastName();
                }
            } else {
                return $customFieldValue->getTextualValue();
            }
        }
    }

    /**
     * Write element to Excel file
     *
     * @param Worksheet $sheet  Excel sheet to write
     * @param integer   $row    Current row
     * @param mixed     $entity Entity to process
     */
    public function process($sheet, $row, $entity)
    {
        $valueContainer = $this->getData($entity);
        $value          = $this->getConvertedData($valueContainer, $entity);
    
        $commentHandled = false;
        if ($valueContainer->hasComment()) {
            switch ($this->config['optionComment']) {
                case Configuration::OPTION_COMMENT_NEWLINE:
                    $cell = $sheet->getCellByColumnAndRow($this->columnIndex, $row);
                    $text = new RichText($cell);
                    $text->createTextRun($value);
                    $textRun = $text->createTextRun(" \n" . $valueContainer->getComment());
                    $textRun->getFont()->setItalic(true);
                    $cell->getStyle()->getAlignment()->setWrapText(true);
                    $commentHandled = true;
                    break;
                case Configuration::OPTION_COMMENT_COMMENT:
                    $sheet->setCellValueByColumnAndRow($this->columnIndex, $row, $value);
                    $comment = $sheet->getCommentByColumnAndRow($this->columnIndex, $row);
                    $text    = new RichText();
                    $text->createTextRun($valueContainer->getComment());
                    $comment->setText($text);
                    $commentHandled = true;
                    break;
            } //switch
        }
        $cell = $sheet->getCellByColumnAndRow($this->columnIndex, $row);
        if (!$commentHandled) {
            $cell->setValue($value);
        }

        if ($this->numberFormat !== null) {
            $cell->getStyle()->getNumberFormat()->setFormatCode(
                $this->numberFormat
            );
        }
        if ($this->dataType !== null) {
            $cell->setDataType($this->dataType);
        }
    }

}
