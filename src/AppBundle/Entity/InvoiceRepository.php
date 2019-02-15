<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for @see Invoice
 */
class InvoiceRepository extends EntityRepository
{
    
    /**
     * Finds by transmitted user
     *
     * @param User $user Related user
     * @return array|Invoice[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['createdBy' => $user->getId()]);
    }
    
    /**
     * Finds for transmitted @see Participation
     *
     * @param Participation $participation Related participation
     * @return array|Invoice[]
     */
    public function findByParticipation(Participation $participation): array
    {
        return $this->findBy(['participation' => $participation->getPid()]);
    }
}
