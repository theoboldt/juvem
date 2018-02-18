<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Payment;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class PaymentSuggestionManager
{

    /**
     * EntityManager
     *
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * CommentManager constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Get price suggestion optimized for participation
     *
     * @param Participation $participation
     * @return PaymentSuggestionList
     */
    public function priceSuggestionsForParticipation(Participation $participation)
    {
        return $this->suggestionListForParticipation($participation, true, false);
    }

    /**
     * Get payment suggestion optimized for participation
     *
     * @param Participation $participation
     * @return PaymentSuggestionList
     */
    public function paymentSuggestionsForParticipation(Participation $participation)
    {
        $list = new PaymentSuggestionList();

        $suggestionForParticipation = $this->suggestionsForParticipation($participation, false, true);
        foreach ($suggestionForParticipation as $suggestionRaw) {
            $suggestion = new PaymentSuggestion(
                ((int)$suggestionRaw['value'])*-1,
                $suggestionRaw['description'],
                (int)$suggestionRaw['count'],
                ['participation']
            );
            $list->add($suggestion);
        }

        return $list;
    }

    /**
     * @param Participation $participation Related participation
     * @param bool          $isPriceSet    If set to true, only price set events are checked
     * @param bool          $isPayment     If set to true, only payment events are checked
     * @return PaymentSuggestionList
     */
    private function suggestionListForParticipation(Participation $participation, bool $isPriceSet, bool $isPayment)
    {
        $list = new PaymentSuggestionList();

        $suggestionForEvent = $this->suggestionsForEvent($participation->getEvent(), $isPriceSet, $isPayment);
        foreach ($suggestionForEvent as $suggestionRaw) {
            $suggestion = new PaymentSuggestion(
                (int)$suggestionRaw['value'], $suggestionRaw['description'], (int)$suggestionRaw['count'], ['event']
            );
            $list->add($suggestion);
        }
        $suggestionForParticipation = $this->suggestionsForParticipation($participation, $isPriceSet, $isPayment);
        foreach ($suggestionForParticipation as $suggestionRaw) {
            $suggestion = new PaymentSuggestion(
                (int)$suggestionRaw['value'],
                $suggestionRaw['description'],
                (int)$suggestionRaw['count'],
                ['participation']
            );
            $list->add($suggestion);
        }

        return $list;
    }

    /**
     * Get all latest payment events for participation
     *
     * @param Participation $participation Related participation
     * @param bool          $isPriceSet    If set to true, only price set events are checked
     * @param bool          $isPayment     If set to true, only payment events are checked
     * @return array
     */
    private function suggestionsForParticipation(Participation $participation, bool $isPriceSet, bool $isPayment)
    {
        $qb = $this->em->getConnection()->createQueryBuilder();
        $qb->select(['y.price_value AS value', 'y.description', 'MAX(y.created_at)'])
           ->from('participant_payment_event', 'y')
           ->innerJoin('y', 'participant', 'a', 'y.aid = a.aid')
           ->innerJoin('a', 'participation', 'p', 'a.pid = p.pid')
           ->andWhere($qb->expr()->eq('p.pid', ':pid'))
           ->setParameter('pid', $participation->getPid())
           ->andWhere('y.is_price_set = :is_price_set')
           ->setParameter('is_price_set', (int)$isPriceSet)
           ->andWhere('y.is_price_payment = :is_price_payment')
           ->setParameter('is_price_payment', (int)$isPayment)
           ->groupBy(['y.price_value', 'y.description']);
        $result = $qb->execute()->fetchAll();

        $suggestions = [];
        foreach ($result as $payment) {
            if (!isset($suggestions[$payment['value']])) {
                $suggestions[$payment['value']] = [];
            }
            if (!isset($suggestions[$payment['value']][$payment['description']])) {
                $suggestions[$payment['value']][$payment['description']] = 0;
            }
            ++$suggestions[$payment['value']][$payment['description']];
        }
        $suggestionsFlat = [];
        foreach ($suggestions as $value => $descriptions) {
            foreach ($descriptions as $description => $count) {
                $suggestionsFlat[] = [
                    'value'       => $value,
                    'description' => $description,
                    'count'       => $count,
                ];
            }
        }
        usort(
            $suggestionsFlat, function ($a, $b) {
            if ($a['count'] == $b['count']) {
                return 0;
            }
            return ($a['count'] < $b['count']) ? -1 : 1;
        }
        );

        return $suggestionsFlat;
    }

    /**
     * Get suggestions for transmitted event grouped by value and description
     *
     * @param Event $event      Related event
     * @param bool  $isPriceSet If set to true, only price set events are checked
     * @param bool  $isPayment  If set to true, only payment events are checked
     * @return array Result
     */
    private function suggestionsForEvent(Event $event, bool $isPriceSet, bool $isPayment)
    {
        $qb = $this->em->getConnection()->createQueryBuilder();
        $qb->select(['y.price_value AS value', 'y.description', 'COUNT(*) AS count'])
           ->from('participant_payment_event', 'y')
           ->innerJoin('y', 'participant', 'a', 'y.aid = a.aid')
           ->innerJoin('a', 'participation', 'p', 'a.pid = p.pid')
           ->andWhere($qb->expr()->eq('p.eid', ':eid'))
           ->setParameter('eid', $event->getEid())
           ->andWhere('y.is_price_set = :is_price_set')
           ->setParameter('is_price_set', (int)$isPriceSet)
           ->andWhere('y.is_price_payment = :is_price_payment')
           ->setParameter('is_price_payment', (int)$isPayment)
           ->groupBy(['y.price_value', 'y.description'])
           ->orderBy('count', 'DESC')
           ->setMaxResults(4);
        return $qb->execute()->fetchAll();
    }
}