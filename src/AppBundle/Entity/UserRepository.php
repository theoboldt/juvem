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
 * Class ParticipationRepository
 *
 * @package AppBundle\Entity
 */
class UserRepository extends EntityRepository
{

    /**
     * Find users which are not yet confirmed
     *
     * @param null|int $createdBeforeDays If specified, only users created before specified date are filtered
     * @return array|User[]
     */
    public function findUnconfirmed(int $createdBeforeDays = null)
    {
        $qb = $this->createQueryBuilder('u')
                   ->andWhere('u.confirmationToken IS NOT NULL')
                   ->andWhere('u.lastLogin IS NULL')
                   ->orderBy('u.nameLast, u.nameFirst', 'ASC');

        if ($createdBeforeDays) {
            $createdBeforeDate = new \DateTime();
            $createdBeforeDate->modify('- ' . (int)$createdBeforeDays . ' days');
            $qb->andWhere($qb->expr()->lt('u.createdAt', ':created_before'))
               ->setParameter('created_before', $createdBeforeDate->format('Y-m-d'));
        }
        return $qb->getQuery()->getResult();
    }
}