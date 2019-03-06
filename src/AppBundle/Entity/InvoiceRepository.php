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
use AppBundle\Entity\Event;

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
    
    /**
     * Finds related to transmitted @see Event
     *
     * @param Event $event Related event
     * @return array|Invoice[]
     */
    public function findByEvent(Event $event): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb->select('i', 'p')
           ->innerJoin('i.participation', 'p')
           ->innerJoin('p.event', 'e')
           ->andWhere($qb->expr()->eq('e.eid', ':eid'))
           ->setParameter('eid', $event->getEid())
           ->indexBy('i', 'i.id')
           ->addOrderBy('p.nameLast')
           ->addOrderBy('p.nameFirst');
        return $qb->getQuery()->execute();
    }
}
