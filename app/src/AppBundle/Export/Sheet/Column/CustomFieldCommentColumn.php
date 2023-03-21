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
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CustomFieldCommentColumn extends EntityAttributeColumn
{

    /**
     * @see Attribute
     */
    protected Attribute $attribute;


    /**
     * Create a new column
     *
     * @param string $identifier   Identifier for document
     * @param string $title        Title text for column
     * @param Attribute $attribute Attribute object
     */
    public function __construct($identifier, $title, Attribute $attribute)
    {
        $this->attribute = $attribute;
        parent::__construct($identifier, $title);
        $this->setNumberFormat(NumberFormat::FORMAT_TEXT);
    }

    /**
     * Get custom field comment value by identifier of this column for transmitted entity
     *
     * @param EntityHavingCustomFieldValueInterface $entity Entity
     * @return  string|null
     */
    public function getData($entity)
    {
        if (!$entity instanceof EntityHavingCustomFieldValueInterface) {
            throw new \InvalidArgumentException(
                'Instance of ' . EntityHavingCustomFieldValueInterface::class . ' expected'
            );
        }
        $valueContainer = $entity->getCustomFieldValues()->getByCustomField($this->attribute);
        return $valueContainer->getComment();
    }

}
