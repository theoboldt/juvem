<?php
namespace AppBundle\Entity;


trait AcquisitionAttributeFilloutTrait
{

    /**
     * Contains the participants assigned to this participation
     *
     * @var array|AcquisitionAttributeFillout[]
     */
    protected $acquisitionAttributeFillouts;

    /**
     * Remove fillout
     *
     * @param AcquisitionAttributeFillout $fillout
     */
    public function removeFillout(AcquisitionAttributeFillout $fillout)
    {
        $this->fillouts->removeElement($fillout);
    }

    /**
     * Add acquisitionAttributeFillout
     *
     * @param AcquisitionAttributeFillout $acquisitionAttributeFillout
     *
     * @return Participation|Participant
     */
    public function addAcquisitionAttributeFillout(AcquisitionAttributeFillout $acquisitionAttributeFillout
    )
    {
        $this->acquisitionAttributeFillouts[] = $acquisitionAttributeFillout;

        return $this;
    }

    /**
     * Remove acquisitionAttributeFillout
     *
     * @param AcquisitionAttributeFillout $acquisitionAttributeFillout
     */
    public function removeAcquisitionAttributeFillout(AcquisitionAttributeFillout $acquisitionAttributeFillout
    )
    {
        $this->acquisitionAttributeFillouts->removeElement($acquisitionAttributeFillout);
    }

    /**
     * Get acquisitionAttributeFillouts
     *
     * @return \Doctrine\Common\Collections\Collection|AcquisitionAttributeFillout[]
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
     * @return AcquisitionAttributeFillout The field
     */
    public function getAcquisitionAttributeFillout($bid, $createIfNotFound = false)
    {
        /** @var AcquisitionAttributeFillout $acquisitionAttribute */
        foreach ($this->getAcquisitionAttributeFillouts() as $fillout) {
                if ($fillout->getBid() == $bid) {
                return $fillout;
            }
        }
        if ($createIfNotFound) {
            $fillout = new AcquisitionAttributeFillout();
            if ($this instanceof Participation) {
                $fillout->setParticipation($this);
                $attributes = $this->getEvent()->getAcquisitionAttributes(true, false);
            } elseif($this instanceof Participant) {
                $fillout->setParticipant($this);
                $attributes = $this->getEvent()->getAcquisitionAttributes(false, true);
            } else {
                throw new \InvalidArgumentException('This acquisition attribute fillout trait is used at unknown class');
            }
            /** @var AcquisitionAttribute $attribute */
            foreach ($attributes as $attribute) {
                if ($attribute->getBid() == $bid) {
                    $fillout->setAttribute($attribute);
                    break;
                }
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
     * @return AcquisitionAttributeFillout
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
     * @return AcquisitionAttributeFillout
     */
    public function __set($key, $value) {
        if (preg_match('/acq_field_(\d+)/', $key, $bidData)) {
            $fillout = $this->getAcquisitionAttributeFillout($bidData[1]);
            $fillout->setValue($value);
        }
        return $this;
    }
}