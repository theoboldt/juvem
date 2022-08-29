<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\FeedbackQuestionnaire;

use AppBundle\Entity\Event;
use Doctrine\ORM\EntityRepository;

/**
 * Repository
 */
class FeedbackQuestionnaireFilloutRepository extends EntityRepository
{

    /**
     * Non-empty response count
     * 
     * @param Event $event
     * @return int
     */
    public function fetchResponseCount(Event $event): int
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select(['c'])
           ->andWhere('c.event = :eventId')
           ->andWhere('c.fillout <> :fillout');

        $qb->setParameter('eventId', $event->getEid());
        $qb->setParameter('fillout', '[]');
        $query = $qb->getQuery();
        
        $responseCount = 0;
        /** @var FeedbackQuestionnaireFillout $feedback */
        foreach ($query->execute() as $feedback) {
            /** @var \AppBundle\Feedback\FeedbackQuestionnaireFillout|null $fillout */
            $fillout = $feedback->getFillout(true);
            
            if ($fillout) {
                foreach ($fillout->getAnswers() as $answer) {
                    $decodedAnswer = $answer->getAnswer();
                    if ($decodedAnswer !== null) {
                        ++$responseCount;
                        break;
                    }
                }
            }
        }
        
        return $responseCount;
    }

    /**
     * @param Event $event
     * @return int
     */
    public function fetchFilloutSubmittedTotalCount(Event $event): int
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select(['COUNT(c)'])
           ->andWhere('c.event = :eventId');

        $qb->setParameter('eventId', $event->getEid());
        
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Fetch all fillouts
     * 
     * @param Event $event
     * @return FeedbackQuestionnaireFillout[]
     */
    public function fetchFillouts(Event $event): array
    {
        return $this->findBy(
            [
                'event' => $event->getEid(),
            ]
        );
    }

}
