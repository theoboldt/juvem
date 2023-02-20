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

class ParticipationCustomFieldCommentColumn extends CustomFieldCommentColumn
{
    /**
     * Get custom field comment value by identifier of this column for transmitted entity
     *
     * @param Participant $entity Entity
     * @return  string|null
     */
    public function getData($entity)
    {
        if (!$entity instanceof Participant) {
            throw new \InvalidArgumentException('Instance of ' . Participant::class . ' expected');
        }
        $participation  = $entity->getParticipation();
        $valueContainer = $participation->getCustomFieldValues()->getByCustomField($this->attribute);
        return $valueContainer->getComment();
    }

}
