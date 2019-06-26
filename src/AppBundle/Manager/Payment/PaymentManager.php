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
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantPaymentEvent;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Manager\Payment\PriceSummand\EntityPriceTag;
use AppBundle\Manager\Payment\PriceSummand\SummandImpactedInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class Payment Manager
 *
 * @package AppBundle\Manager
 */
class PaymentManager
{

    /**
     * EntityManager
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * The user currently logged in
     *
     * @var User|null
     */
    protected $user = null;

    /**
     * PriceManager
     *
     * @var PriceManager
     */
    private $priceManager;

    /**
     * Cache for all payments
     *
     * @var array|ParticipantPaymentEvent[]
     */
    private $paymentCache = [];

    /**
     * Convert value in euro to value in euro cents
     *
     * @param   string|float|int $priceInEuro Value in euros, separated by comma
     * @return  float|int
     */
    public static function convertEuroToCent($priceInEuro)
    {
        $priceInEuro = trim($priceInEuro);
        if (empty($priceInEuro)) {
            return 0;
        } elseif (preg_match('/^(?:[^\d]*?)([-]{0,1})(\d+)(?:[,.]{0,1})(\d*)$/', $priceInEuro, $priceParts)) {
            $euros = $priceParts[2] ?: 0;
            if ($priceParts[3]) {
                $cents = substr_replace(str_pad($priceParts[3], 2, '0'), '.', 2, 0) . '0';
            } else {
                $cents = 0;
            }

            $priceInCents = ($euros * 100 + $cents);

            if ($priceParts[1] === '-') {
                $priceInCents *= -1;
            }

            return $priceInCents;
        } else {
            throw new \InvalidArgumentException('Failed to convert "' . $priceInEuro . '" to euro cents');
        }
    }

    /**
     * CommentManager constructor.
     *
     * @param EntityManagerInterface $em
     * @param PriceManager           $priceManager
     * @param TokenStorage           $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $em,
        PriceManager $priceManager,
        TokenStorage $tokenStorage = null
    ) {
        $this->em           = $em;
        $this->priceManager = $priceManager;
        if ($tokenStorage && $tokenStorage->getToken()) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
    }
    
    /**
     * Provides participant payment status
     *
     * @param Participant $participant Participant
     * @return ParticipantPaymentStatus Status
     */
    public function getParticipantPaymentStatus(Participant $participant): ParticipantPaymentStatus
    {
        return new ParticipantPaymentStatus($this, $participant);
    }

    /**
     * Set price for multiple participants
     *
     * @param array     $participants List of participants where this operation should be applied to
     * @param int|float $value        New price (euro cent)
     * @param string    $description  Description for change
     * @return array|ParticipantPaymentEvent[]
     * @throws \Throwable
     */
    public function setBasePrice(array $participants, $value, string $description)
    {
        $em = $this->em;
        /** @var Participant $participant */

        return $em->transactional(
            function (EntityManager $em) use ($participants, $value, $description) {
                $events = [];
                /** @var Participant $participant */
                foreach ($participants as $participant) {
                    $event = ParticipantPaymentEvent::createPriceSetEvent(
                        $this->user, $value, $description
                    );
                    $participant->addPaymentEvent($event);
                    $participant->setBasePrice($value);
                    $em->persist($event);
                    $em->flush($event); //ensure events are on db before fetching for to pay cache

                    $em->persist($participant);
                    $events[] = $event;
                }
                $em->flush();
                return $events;
            }
        );
    }
    
    /**
     * Reset payment cache for transmitted ids
     *
     * @param int $eid      Related event id
     * @param int|null $aid If just single participant affected, transmit id
     * @return void
     */
    private function resetPaymentCache(int $eid, int $aid = null): void
    {
        if ($aid) {
            if (isset($this->paymentCache[$eid][$aid])) {
                unset($this->paymentCache[$eid][$aid]);
            }
        } else {
            if (isset($this->paymentCache[$eid])) {
                unset($this->paymentCache[$eid]);
            }
        }
    }
    
    
    /**
     * Fetch all payment events for @see Event
     *
     * @param Event $event
     */
    private function fetchPaymentHistoryForEvent(Event $event) {
        $eid = $event->getEid();
        /** @var EventRepository $eventRepository */
        $eventRepository          = $this->em->getRepository(Event::class);
        $participants             = $eventRepository->participantAidsForEvent($event);
        $this->paymentCache[$eid] = array_fill_keys($participants, []);

        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->innerJoin('a.participation', 'p')
           ->andWhere($qb->expr()->eq('p.event', ':eid'))
           ->orderBy('e.createdAt', 'DESC');
        $result = $qb->getQuery()->execute(['eid' => $eid]);
        /** @var ParticipantPaymentEvent $paymentEvent */
        foreach ($result as $paymentEvent) {
            $aid = $paymentEvent->getParticipant()->getAid();
            if (!isset($this->paymentCache[$eid][$aid])) {
                $this->paymentCache[$eid][$aid] = [];
            }
            $this->paymentCache[$eid][$aid][] = $paymentEvent;
        }
    }

    /**
     * Get summands for calculating price of participant
     *
     * @param SummandImpactedInterface $impactedEntity Either @see Participant, or @see Employee
     * @return EntityPriceTag
     */
    public function getEntityPriceTag(SummandImpactedInterface $impactedEntity)
    {
        return $this->priceManager->getEntityPriceTag($impactedEntity);
    }

    /**
     * Get all payment events for transmitted @see Participant
     *
     * @param Participant $participant Desired participant
     * @return array|ParticipantPaymentEvent[]
     */
    public function getPaymentHistoryForParticipant(Participant $participant)
    {
        $event = $participant->getEvent();
        $eid   = $event->getEid();
        if (!isset($this->paymentCache[$eid]) || !isset($this->paymentCache[$eid][$participant->getAid()])) {
            $this->fetchPaymentHistoryForEvent($participant->getEvent());
        }
        return $this->paymentCache[$eid][$participant->getAid()];
    }

     /**
     * Get all payment events for transmitted @see Participants
     *
     * @param array|Participant[] $participants List of Participants
     * @return array|ParticipantPaymentEvent[]
     */
    public function getPaymentHistoryForParticipantList(array $participants)
    {
        $paymentEvents = [];
        foreach ($participants as $participant) {
            $paymentEvents = array_merge($paymentEvents, $this->getPaymentHistoryForParticipant($participant));
        }
        uasort($paymentEvents, function(ParticipantPaymentEvent $a, ParticipantPaymentEvent $b) {
            if ($a->getCreatedAt()->format('U') === $b->getCreatedAt()->format('U')) {
                return 0;
            }
            return ($a > $b) ? -1 : 1;
        });

        return $paymentEvents;
    }

    /**
     * Payment for a multiple participants received
     *
     * @param Participant[] $participantsUnordered Participant on which operation will be applied to
     * @param int|float     $value                 Numeric value of the payment event - Note that a negative sign
     *                                             indicates a reduction of price which still needs to be payed, a
     *                                             positive indicates a reverse booking, which results in increase of
     *                                             the value which still needs to be payed
     * @param string        $description           Info text for payment event
     * @return ParticipantPaymentEvent[]           New created payment event
     * @throws \Throwable
     */
    public function handlePaymentForParticipants(array $participantsUnordered, $value, string $description)
    {
        $participants = [];
        /** @var Participant $participant */
        foreach ($participantsUnordered as $participant) {
            if ($participant->isWithdrawn() || $participant->isRejected() || $participant->getDeletedAt()) {
                continue; //do not take into account
            }
            $participants[$participant->getAid()] = $participant;
        }
        unset($participantsUnordered);

        if (!count($participants)) {
            return [];
        }

        return $this->em->transactional(
            function () use ($participants, $value, $description) {
                $toPayList     = [];
                $participation = null;

                /** @var Participant $participant */
                foreach ($participants as $participant) {
                    $aid   = $participant->getAid();
                    $toPay = $this->getToPayValueForParticipant($participant, false);
                    if ($toPay === null) {
                        continue;
                    }
                    $toPayList[$aid] = $toPay;
                    if (!$participation) {
                        $participation = $participant->getParticipation();
                    }
                }
                if (!count($toPayList)) {
                    return [];
                }

                $paymentsMade = array_fill_keys(array_keys($toPayList), 0);

                $valueLeft = $value;
                if ((array_sum($toPayList) + $value) >= 0 && count(array_count_values($toPayList)) === 1) {
                    //all participants cost same, so distribute payment equally
                    //but not in case of overpayment
                    $toPay        = $value / count($toPayList);
                    $paymentsMade = array_fill_keys(array_keys($toPayList), $toPay);

                } else {
                    //start with first to pay list
                    foreach ($toPayList as $aid => $toPay) {
                        $valueLeft = $toPay + $valueLeft;
                        if ($valueLeft <= 0) {
                            $paymentsMade[$aid] = $toPay * -1;
                        } else {
                            //not enough for full payment
                            $paymentsMade[$aid] = ($toPay - $valueLeft) * -1;
                            break;
                        }
                    }

                    //there's still something left, positive or negative, add it to first participant
                    reset($paymentsMade);
                    $aid                = key($paymentsMade);
                    $paymentsMade[$aid] += $valueLeft;
                }
                $payments = [];
                foreach ($paymentsMade as $aid => $paymentValue) {
                    /** @var Participant $participant */
                    $participant = $participants[$aid];
                    $payment     = ParticipantPaymentEvent::createPaymentEvent(
                        $this->user, $paymentValue, $description
                    );
                    $participant->addPaymentEvent($payment);
                    $this->em->persist($payment);
                    $this->em->flush($payment); //ensure payment event is stored

                    $this->em->persist($participant);
                    $payments[] = $payment;
                    $this->resetPaymentCache($participant->getEvent()->getEid(), $participant->getAid());
                }
                $this->em->flush();
                return $payments;
            }
        );
    }

    /**
     * Payment for a single participant received
     *
     * @param Participant $participant Participant on which operation will be applied to
     * @param int|float   $value       Numeric value of the payment event - Note that a negative sign indicates a
     *                                 reduction of price which still needs to be payed, a positive indicates a reverse
     *                                 booking, which results in increase of the value which still needs to be payed
     * @param string      $description Info text for payment event
     * @return ParticipantPaymentEvent New created payment event
     * @throws \Throwable
     */
    public function handlePaymentForParticipant(Participant $participant, $value, string $description)
    {
        return $this->em->transactional(
            function () use ($participant, $value, $description) {
                $payment = ParticipantPaymentEvent::createPaymentEvent($this->user, $value, $description);
                $participant->addPaymentEvent($payment);

                $this->em->persist($payment);
                $this->em->flush();
                $this->resetPaymentCache($participant->getEvent()->getEid(), $participant->getAid());
                return $payment;
            }
        );
    }

    /**
     * Get amount of money which still needs to be paid for a complete @see Participation
     *
     * @param Participation $participation Target participation
     * @param bool          $inEuro        If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|null                    Value needed to be payed
     */
    public function getToPayValueForParticipation(Participation $participation, $inEuro = false)
    {
        $allNull     = true;
        $toPayValues = [];
        foreach ($participation->getParticipants() as $participant) {
            $toPayValue    = $this->getToPayValueForParticipant($participant, $inEuro);
            $toPayValues[] = $toPayValue;
            $allNull       = $allNull && $toPayValue === null;
        }
        if ($allNull) {
            return null;
        }

        return array_sum($toPayValues);
    }

    /**
     * Get amount of money which still needs to be paid for a single @see Participant
     *
     * @param Participant $participant Target participant
     * @param bool        $inEuro      If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|null                Value needed to be payed
     */
    public function getToPayValueForParticipant(Participant $participant, $inEuro = false)
    {
        $price = $this->getPriceForParticipant($participant, $inEuro);
        if ($price === null) {
            //no price set event for current participant present, default price of event is not used
            return null;
        }

        return $price + $this->getParticipantPaymentHistorySum($participant, $inEuro);
    }
    
    /**
     * Get sum of all transactions; Payments are less than zero
     *
     * @param Participant $participant Target participant
     * @param bool $inEuro             If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|null                Sum of transactions, note that negative transactions mean payments
     */
    public function getParticipantPaymentHistorySum(Participant $participant, $inEuro = false)
    {
        $fullHistory = $this->getPaymentHistoryForParticipant($participant);
        $topPay      = 0;
        /** @var ParticipantPaymentEvent $event */
        foreach ($fullHistory as $event) {
            if ($event->isPricePaymentEvent()) {
                $topPay += $event->getValue($inEuro);
            }
        }
        return $topPay;
    }
    
    /**
     * Get sum of all transactions; Payments are less than zero
     *
     * @param Participation $participation Target participant
     * @param bool $inEuro                 If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|null                    Sum of transactions, note that negative transactions mean payments
     */
    public function getParticipationPaymentHistorySum(Participation $participation, $inEuro = false)
    {
        $participants = $participation->getParticipants();
        $topPay       = 0;
        foreach ($participants as $participant) {
            $topPay += $this->getParticipantPaymentHistorySum($participant, $inEuro);
        }
        
        return $topPay;
    }

    /**
     * Determine if (some) payment is still missing for transmitted @see Participation
     *
     * @param Participation $participation Related participation
     * @return bool|null                   True if still missing, null if no price set, false if fully paid
     */
    public function isParticipationRequiringPayment(Participation $participation)
    {
        $toPay = $this->getToPayValueForParticipation($participation);
        if ($toPay === null) {
            return null;
        }
        return $toPay > 0;
    }
    
    /**
     * Determine if (some) payment is still missing for transmitted @see Participant
     *
     * @param Participant $participant Related participant
     * @return bool|null               True if still missing, null if no price set, false if fully paid
     */
    public function isParticipantRequiringPayment(Participant $participant) {
        $toPay = $this->getToPayValueForParticipant($participant);
        if ($toPay === null) {
            return null;
        }
        return $toPay > 0;
    }

    /**
     * Get price for transmitted participation
     *
     * @param Participation $participation Target Participation
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return float|int|null
     */
    public function getPriceForParticipation(Participation $participation, $inEuro = false)
    {
        return $this->priceManager->getPriceForParticipation($participation, $inEuro);
    }

    /**
     * Get price for transmitted participant
     *
     * @param Participant $participant Target Participant
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return float|int|null
     */
    public function getPriceForParticipant(Participant $participant, $inEuro = false)
    {
        return $this->priceManager->getPriceForParticipant($participant, $inEuro);
    }
}
