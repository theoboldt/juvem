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
            $similarEventIdList = array(-1);
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
          LEFT JOIN event_newsletter_subscription es ON (s.rid = es.rid)
              WHERE is_enabled = 1
                AND (
                      ( ? <= IF((base_age IS NOT NULL), (age_range_end + %1$s), age_range_end)
                        AND ? >= IF((base_age IS NOT NULL), (age_range_begin + %1$s), age_range_begin)
                      ) OR es.eid IN (%2$s)
                    );
              ',
            $queryAgeRangeClearance,
            implode($similarEventIdList, ',')
        );

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);
        $stmt->execute(array($ageRangeBegin, $ageRangeEnd));

        return $stmt->fetchColumn();
    }

}
