<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Form;


use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\FilloutTrait;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;

interface EntityHavingFilloutsInterface
{
    
    /**
     * Get entity id related to entities namespace
     *
     * @return int
     */
    public function getId();
    
    /**
     * Remove fillout
     *
     * @param Fillout $fillout
     */
    public function removeFillout(Fillout $fillout);

    /**
     * Add acquisitionAttributeFillout
     *
     * @param Fillout $acquisitionAttributeFillout
     *
     * @return Participation|Participant|Employee|FilloutTrait
     */
    public function addAcquisitionAttributeFillout(Fillout $acquisitionAttributeFillout);

    /**
     * Get acquisitionAttributeFillouts
     *
     * @return \Doctrine\Common\Collections\Collection|Fillout[]
     */
    public function getAcquisitionAttributeFillouts();

    /**
     * Get acquisition attribute fillout with given bid
     *
     * @param int  $bid              The id of the field
     * @param bool $createIfNotFound Set to true to create a new fillout entry or this entity and attribute
     * @return Fillout The field
     */
    public function getAcquisitionAttributeFillout($bid, $createIfNotFound = false);
}
