<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * FlashRepository
 */
class FlashRepository extends EntityRepository
{

    /**
     * Fetch all events ordered by title
     *
     * @return array
     */
    public function findAllOrderedByTitle()
    {
        return $this->getEntityManager()
                    ->createQuery(
                        'SELECT e FROM AppBundle:EVENT e ORDER BY e.title ASC'
                    )
                    ->getResult();
    }

    /**
     * Get list of currently valid flash messages
     *
     * @return  array|Flash[]
     */
    public function findValid()
    {
        $qb = $this->createQueryBuilder('f');
        $qb->andWhere($qb->expr()->orX('f.validFrom IS NULL', 'f.validFrom >= CURRENT_TIMESTAMP()'))
           ->andWhere($qb->expr()->orX('f.validUntil IS NULL', 'f.validUntil <= CURRENT_TIMESTAMP()'))
           ->orderBy('f.validUntil', 'ASC');

        $query = $qb->getQuery();

        return $query->execute();
    }

}
