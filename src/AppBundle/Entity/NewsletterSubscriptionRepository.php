<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for newsletter subscriptions
 */
class NewsletterSubscriptionRepository extends EntityRepository
{

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
    public function qualifiedNewsletterSubscriptionIdList($ageRangeBegin, $ageRangeEnd, array $similarEventIdList = null)
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
            'FLOOR(DATEDIFF( CURDATE(), base_age) / %d)', EventRepository::DAYS_OF_YEAR
        );
        $query                  = sprintf(
            'SELECT DISTINCT s.rid
               FROM newsletter_subscription s
          LEFT JOIN event_newsletter_subscription es ON (s.rid = es.rid AND es.eid IN (%2$s))
              WHERE is_enabled = 1
                AND (
                      ( :ageRangeBegin <= (CASE WHEN (s.base_age IS NOT NULL) THEN (s.age_range_end + %1$s) ELSE s.age_range_end END)
                        AND :ageRangeEnd >= (CASE WHEN (s.base_age IS NOT NULL) THEN (s.age_range_begin + %1$s) ELSE s.age_range_begin END)
                      ) OR es.rid IS NOT NULL
                    )
              ',
            $queryAgeRangeClearance,
            implode($similarEventIdList, ',')
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
     * @see qualifiedNewsletterSubscriptionQuery()
     * @param int      $ageRangeBegin             Begin of interesting age range
     * @param int      $ageRangeEnd               Begin of interesting age range
     * @param array    $similarEventIdList        List of subscribed event ids
     * @param int|null $excludeFromNewsletter     Define newsletter id here to exclude subscriptions which have already
     *                                            received the transmitted newsletter
     * @return array|NewsletterSubscription[]
     */
    public function qualifiedNewsletterSubscriptionList(
        $ageRangeBegin, $ageRangeEnd, array $similarEventIdList = null, $excludeFromNewsletter = null
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
