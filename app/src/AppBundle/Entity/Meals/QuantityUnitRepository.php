<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity\Meals;

use Doctrine\ORM\EntityRepository;


class QuantityUnitRepository extends EntityRepository
{
    
    public function findAllKeyed(): array
    {
        $units = [];
        /** @var QuantityUnit $unit */
        foreach ($this->findAll() as $unit) {
            $units[$unit->getId()] = $unit;
        }
        return $units;
    }
    
}