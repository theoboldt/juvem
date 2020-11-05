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
use AppBundle\Entity\AcquisitionAttribute\FilloutTrait;
use AppBundle\Entity\Participant;

class ParticipationAcquisitionAttributeColumn extends AcquisitionAttributeAttributeColumn
{
    /**
     * Get value by identifier of this column for transmitted entity
     *
     * @param   Participant $entity Entity
     * @return  mixed
     */
    public function getData($entity)
    {
        $participation = $entity->getParticipation();

        try {
            $fillout = $participation->getAcquisitionAttributeFillout($this->attribute->getBid());
        } catch (\OutOfBoundsException $e) {
            $fillout = null;
        }
        return $fillout;
    }

}