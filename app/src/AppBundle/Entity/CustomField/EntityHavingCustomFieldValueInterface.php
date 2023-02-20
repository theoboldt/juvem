<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\CustomField;

use AppBundle\Entity\Event;

interface EntityHavingCustomFieldValueInterface
{

    /**
     * Get entity id related to entities namespace
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Get event from participation
     *
     * @return Event|null
     */
    public function getEvent(): ?Event;

    /**
     * Get custom field value collection
     *
     * @return CustomFieldValueCollection
     */
    public function getCustomFieldValues(): CustomFieldValueCollection;

}
