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
     * Get the amount of newsletter subscriptions which qualifies for transmitted parameters
     *
     * @return  int $ageRangeBegin      Begin of interesting age range
     * @return  int $ageRangeEnd        Begin of interesting age range
     * @param array $similarEventIdList List of subscribed event ids
     * @return  int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function qualifiedNewsletterSubscriptionCount($ageRangeBegin, $ageRangeEnd, array $similarEventIdList = null)
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
            'SELECT COUNT(*)
               FROM newsletter_subscription s
          LEFT JOIN event_newsletter_subscription es ON (s.rid = es.rid AND es.eid IN (%2$s))
              WHERE is_enabled = 1
                AND (
                      ( :ageRangeBegin <= IF((base_age IS NOT NULL), (age_range_end + %1$s), age_range_end)
                        AND :ageRangeEnd >= IF((base_age IS NOT NULL), (age_range_begin + %1$s), age_range_begin)
                      ) OR es.eid IN (%2$s)
                    )
              ',
            $queryAgeRangeClearance,
            implode($similarEventIdList, ',')
        );

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array('ageRangeBegin' => $ageRangeBegin, 'ageRangeEnd' => $ageRangeEnd));

        return $stmt->fetchColumn();
    }

    /**
     * Get the list of newsletter subscriptions which qualifies for transmitted parameters
     *
     *
     * @param int   $ageRangeBegin             Begin of interesting age range
     * @param int   $ageRangeEnd               Begin of interesting age range
     * @param array $similarEventIdList        List of subscribed event ids
     * @param array $similarEventIdList        List of subscribed event ids
     * @param bool  $excludeFromSentNewsletter Set to true to exclude subscriptions which have already received this
     *                                         newsletter
     * @return array|NewsletterSubscription[]
     */
    public function qualifiedNewsletterSubscriptionList(
        $ageRangeBegin, $ageRangeEnd, array $similarEventIdList = null, $excludeFromSentNewsletter = false
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
            'FLOOR(DATEDIFF( CURDATE(), s.baseAge) / %d)', EventRepository::DAYS_OF_YEAR
        );
        $dql                    = sprintf(
            'SELECT DISTINCT s
               FROM %3$s s
          LEFT JOIN s.events es WITH (es.eid IN (%2$s))
              WHERE s.isEnabled = 1
                AND (
                      ( :ageRangeBegin <= (CASE WHEN (s.baseAge IS NOT NULL) THEN (s.ageRangeEnd + %1$s) ELSE s.ageRangeEnd END)
                        AND :ageRangeEnd >= (CASE WHEN (s.baseAge IS NOT NULL) THEN (s.ageRangeBegin + %1$s) ELSE s.ageRangeBegin END)
                      ) OR es.eid IN (%2$s)
                    )

              ',
            $queryAgeRangeClearance,
            implode($similarEventIdList, ','),
            NewsletterSubscription::class
        );

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameters(
            array(
                'ageRangeBegin' => $ageRangeBegin,
                'ageRangeEnd'   => $ageRangeEnd
            )
        );
        return $query->getResult();
    }
}
