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

interface OptionProvidingCustomFieldValueInterface
{
    /**
     * @return int[]
     */
    public function getSelectedChoices(): array;
}
