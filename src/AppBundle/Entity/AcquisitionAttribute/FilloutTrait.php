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


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;

trait FilloutTrait
{

    /**
     * Contains the participants assigned to this participation
     *
     * @var \Doctrine\Common\Collections\Collection|Fillout[]
     */
    protected $acquisitionAttributeFillouts;

    /**
     * Remove fillout
     *
     * @param Fillout $fillout
     */
    public function removeFillout(Fillout $fillout)
    {
        $this->acquisitionAttributeFillouts->removeElement($fillout);
    }

    /**
     * Add acquisitionAttributeFillout
     *
     * @param Fillout $acquisitionAttributeFillout
     *
     * @return Participation|Participant|FilloutTrait
     */
    public function addAcquisitionAttributeFillout(Fillout $acquisitionAttributeFillout)
    {
        $this->acquisitionAttributeFillouts[] = $acquisitionAttributeFillout;

        return $this;
    }

    /**
     * Remove acquisitionAttributeFillout
     *
     * @param Fillout $acquisitionAttributeFillout
     */
    public function removeAcquisitionAttributeFillout(Fillout $acquisitionAttributeFillout
    )
    {
        $this->acquisitionAttributeFillouts->removeElement($acquisitionAttributeFillout);
    }

    /**
     * Get acquisitionAttributeFillouts
     *
     * @return \Doctrine\Common\Collections\Collection|Fillout[]
     */
    public function getAcquisitionAttributeFillouts()
    {
        return $this->acquisitionAttributeFillouts;
    }

    /**
     * Get acquisition attribute fillout with given bid
     *
     * @param int  $bid The id of the field
     * @param bool $createIfNotFound    Set to true to create a new fillout entry or this entity and attribute
     * @return Fillout The field
     */
    public function getAcquisitionAttributeFillout($bid, $createIfNotFound = false)
    {
        /** @var Fillout $acquisitionAttribute */
        foreach ($this->getAcquisitionAttributeFillouts() as $fillout) {
                if ($fillout->getBid() == $bid) {
                return $fillout;
            }
        }
        if ($createIfNotFound) {
            /** @var Event $event */
            $event = $this->getEvent();
            if (!$event) {
                throw new \InvalidArgumentException('Can not create fillout if no related event is configured');
            }

            $fillout = new Fillout();
            if ($this instanceof Participation) {
                $fillout->setParticipation($this);
                $attributes = $event->getAcquisitionAttributes(true, false, false, true, true);
            } elseif($this instanceof Participant) {
                $fillout->setParticipant($this);
                $attributes = $event->getAcquisitionAttributes(false, true, false, true, true);
            } else {
                throw new \InvalidArgumentException('This acquisition attribute fillout trait is used at unknown class');
            }
            /** @var Attribute $attribute */
            foreach ($attributes as $attribute) {
                if ((int)$attribute->getBid() === (int)$bid) {
                    $fillout->setAttribute($attribute);
                    break;
                }
            }
            if (!$fillout->getAttribute()) {
                throw new \InvalidArgumentException('No attribute for fillout found');
            }
            $this->addAcquisitionAttributeFillout($fillout);
            return $fillout;

        }
        throw new \OutOfBoundsException('Requested fillout was not found');
    }

    /**
     * Getter for acquisition attribute mapping
     *
     * @param   string  $key                Key containing name of fillout attribute
     * @return Fillout
     */
    public function __get($key) {
        if (preg_match('/acq_field_(\d+)/', $key, $bidData)) {
            return $this->getAcquisitionAttributeFillout($bidData[1], true);
        }
        throw new \InvalidArgumentException('Unknown property accessed');
    }

    /**
     * Getter for acquisition attribute mapping
     *
     * @param   string  $key    Key containing name of fillout attribute
     * @param   mixed   $value  New value for this fillout
     * @return FilloutTrait
     */
    public function __set($key, $value) {
        if (preg_match('/acq_field_(\d+)/', $key, $bidData)) {
            $fillout = $this->getAcquisitionAttributeFillout($bidData[1]);
            $fillout->setValue($value);
        }
        return $this;
    }
}
