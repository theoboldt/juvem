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


use AppBundle\Export\AttributeOptionExplanation;

interface SheetRequiringExplanationInterface
{
    /**
     * Get  list of all attached @see AttributeOptionExplanation
     *
     * @return AttributeOptionExplanation[]|array
     */
    public function getExplanations(): array;
}
