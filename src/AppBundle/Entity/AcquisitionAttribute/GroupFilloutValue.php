<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\AcquisitionAttribute;


class GroupFilloutValue extends ChoiceFilloutValue
{

    /**
     * Get group id
     *
     * @return null|int
     */
    public function getGroupId()
    {
        if ($this->value === null) {
            return null;
        }
        $choices = $this->getSelectedChoices();
        if (count($choices) > 1) {
            throw new \InvalidArgumentException('Group fillout must contain only a single selected choice');
        } elseif (!count($choices)) {
            return null;
        }
        $choice = reset($choices);

        return $choice->getId();
    }

}
