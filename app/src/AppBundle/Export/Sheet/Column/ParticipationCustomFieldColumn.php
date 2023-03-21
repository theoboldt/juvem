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


use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\Participant;

class ParticipationCustomFieldColumn extends CustomFieldColumn
{
    /**
     * Extract {@see CustomFieldValueContainer} from transmitted entity
     *
     * @param EntityHavingCustomFieldValueInterface $entity Entity
     * @return CustomFieldValueContainer
     */
    protected function extractCustomFieldValueContainer(EntityHavingCustomFieldValueInterface $entity
    ): CustomFieldValueContainer {
        if (!$entity instanceof Participant) {
            throw new \InvalidArgumentException('Instance of ' . Participant::class . ' expected');
        }
        $participation = $entity->getParticipation();
        return $participation->getCustomFieldValues()->getByCustomField($this->attribute);
    }
}
