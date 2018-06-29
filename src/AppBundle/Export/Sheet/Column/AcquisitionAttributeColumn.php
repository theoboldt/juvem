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


use AppBundle\Entity\AcquisitionAttribute;
use AppBundle\Entity\AcquisitionAttributeFilloutTrait;

class AcquisitionAttributeColumn extends EntityColumn
{

    /**
     * @see AcquisitionAttribute
     */
    protected $attribute;

    /**
     * Create a new column
     *
     * @param string               $identifier Identifier for document
     * @param string               $title      Title text for column
     * @param AcquisitionAttribute $attribute  Attribute object
     */
    public function __construct($identifier, $title, AcquisitionAttribute $attribute)
    {
        $this->attribute = $attribute;
        parent::__construct($identifier, $title);
    }


    /**
     * Get value by identifier of this column for transmitted entity
     *
     * @param   AcquisitionAttributeFilloutTrait $entity Entity
     * @return  mixed
     */
    public function getData($entity)
    {
        try {
            $fillout = $entity->getAcquisitionAttributeFillout($this->attribute->getBid());
        } catch (\OutOfBoundsException $e) {
            $fillout = null;
        }
        return $fillout;
    }

}