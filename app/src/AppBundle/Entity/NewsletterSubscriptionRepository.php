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
 * Repository for newsletter subscriptions
 */
class NewsletterSubscriptionRepository extends EntityRepository
{

    /**
     * Finds a single entity by transmitted user id
     *
     * @param User $user Related user
     * @return  NewsletterSubscription|null
     */
    public function findOneByUser(User $user)
    {
        return $this->findOneBy(['assignedUser' => $user->getId()]);
    }

    /**
     * Finds a single entity by transmitted e-mail address
     *
     * @param   string $email E-mail uniquely identifying an subscription
     * @return  NewsletterSubscription|null
     */
    public function findOneByEmail($email)
    {
        return $this->findOneBy(array('email' => $email));
    }

    /**
     * Finds a single entity by transmitted disable/management token
     *
     * @param   string $token Token uniquely identifying an subscription
     * @return  NewsletterSubscription|null
     */
    public function findOneByToken($token)
    {
        return $this->findOneBy(array('disableToken' => $token));
    }

    /**
     * Get the list of newsletter subscription ids which qualifies for transmitted parameters
     *
     * @see qualifiedNewsletterSubscriptionQuery()
     * @param   int   $ageRangeBegin      Begin of interesting age range
     * @param   int   $ageRangeEnd        Begin of interesting age range
     * @param   array $similarEventIdList List of subscribed event ids
     * @return  int[]|array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function qualifiedNewsletterSubscriptionIdList(
        int $ageRangeBegin, int $ageRangeEnd, array $similarEventIdList = null
    )
    {
        if (!$similarEventIdList || !count($similarEventIdList)) {
            $similarEventIdList = array(0);
        }
        array_walk(
            $similarEventIdList,
            function (&$val) {
                $val = (int)$val;
            }
        );

        /** @var \DateTime $start */
        $queryAgeRangeClearance = sprintf(
            'FLOOR(ABS(DATEDIFF( CURDATE(), base_age)) / %d)', EventRepository::DAYS_OF_YEAR
        );

        if ($ageRangeEnd === 18) {
            $queryAgeOver18rangeAddition = sprintf(
                'OR (s.base_age IS NOT NULL AND (s.age_range_end + %1$s) >= 18)', $queryAgeRangeClearance
            );
        } else {
            $queryAgeOver18rangeAddition = '';
        }

        $query                  = sprintf(
            'SELECT DISTINCT s.rid
               FROM newsletter_subscription s
          LEFT JOIN event_newsletter_subscription es ON (s.rid = es.rid AND es.eid IN (%2$s))
              WHERE is_enabled = 1
                AND is_confirmed = 1
                AND (
                      ( :ageRangeBegin <= (CASE WHEN (s.base_age IS NOT NULL) THEN (s.age_range_end + %1$s) ELSE s.age_range_end END)
                        AND :ageRangeEnd >= (CASE WHEN (s.base_age IS NOT NULL) THEN (s.age_range_begin + %1$s) ELSE s.age_range_begin END)
                      ) OR es.rid IS NOT NULL
                      %3$s
                    )
              ',
            $queryAgeRangeClearance,
            implode(',', $similarEventIdList),
            $queryAgeOver18rangeAddition
        );
        $stmt  = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute(array('ageRangeBegin' => $ageRangeBegin, 'ageRangeEnd' => $ageRangeEnd));

        $qualifiedIds = [];
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $qualifiedIds[] = $row[0];
        }
        return $qualifiedIds;
    }

    /**
     * Get the list of newsletter subscriptions which qualifies for transmitted parameters
     *
     * @param int $ageRangeBegin                  Begin of interesting age range
     * @param int $ageRangeEnd                    Begin of interesting age range
     * @param array|null $similarEventIdList      List of subscribed event ids
     * @param int|null $excludeFromNewsletter     Define newsletter id here to exclude subscriptions which have already
     *                                            received the transmitted newsletter
     * @return array|NewsletterSubscription[]
     * @see qualifiedNewsletterSubscriptionQuery()
     */
    public function qualifiedNewsletterSubscriptionList(
        int $ageRangeBegin, int $ageRangeEnd, ?array $similarEventIdList = null, $excludeFromNewsletter = null
    )
    {
        if ($excludeFromNewsletter instanceof Newsletter) {
            $excludeFromNewsletter = $excludeFromNewsletter->getLid();
        }
        $qualifiedIds = $this->qualifiedNewsletterSubscriptionIdList($ageRangeBegin, $ageRangeEnd, $similarEventIdList);

        $qb = $this->createQueryBuilder('r');
        $qb->addSelect('u')
           ->leftJoin('r.assignedUser', 'u')
           ->andWhere("r.rid IN(:qualifiedIds)")
           ->setParameter('qualifiedIds', $qualifiedIds)
           ->orderBy('r.nameLast', 'ASC');

        if ($excludeFromNewsletter) {
            $qbRecieved = $this->getEntityManager()->createQueryBuilder();
            $qbRecieved->select('enrr.rid')
                ->from(Newsletter::class, 'enr')
                ->innerJoin('enr.recipients', 'enrr')
                ->andWhere($qbRecieved->expr()->eq('enr.lid', (int)$excludeFromNewsletter));
            $qb->andWhere($qb->expr()->notIn('r.rid', $qbRecieved->getDQL()));
        }

        $query = $qb->getQuery();

        return $query->execute();
    }
}
