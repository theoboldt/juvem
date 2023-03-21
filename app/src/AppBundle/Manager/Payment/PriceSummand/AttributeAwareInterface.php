<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Payment\PriceSummand;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\CustomField\CustomFieldValueInterface;

interface AttributeAwareInterface
{

    /**
     * Get related attribute
     *
     * @return Attribute
     */
    public function getAttribute(): Attribute;


    /**
     * Get custom field value
     *
     * @return CustomFieldValueInterface
     */
    public function getCustomFieldValue(): CustomFieldValueInterface;
}
