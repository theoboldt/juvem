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


use Doctrine\ORM\EntityRepository;

class AcquisitionAttributeRepository extends EntityRepository
{
    /**
     * Find one single events having acquisition attributes joined
     *
     * @param int $bid Id of desired @see Attribute
     * @return Attribute|null
     */
    public function findWithOptions(int $bid)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a', 'o')
           ->leftJoin('a.choiceOptions', 'o')
           ->andWhere($qb->expr()->eq('a.bid', ':bid'))
           ->setParameter('bid', $bid);
        $result = $qb->getQuery()->execute();
        if (count($result)) {
            $result = reset($result);
            return $result;
        }
        return null;
    }
}
