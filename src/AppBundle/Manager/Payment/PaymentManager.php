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

use AppBundle\Entity\CommentRepositoryBase;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantPaymentEvent;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
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
     * Price event repository
     *
     * @var CommentRepositoryBase
     */
    protected $repository;

    /**
     * The user currently logged in
     *
     * @var User|null
     */
    protected $user = null;

    /**
     * Convert value in euro to value in euro cents
     *
     * @param   string|float|int $priceInEuro Value in euros, separated by comma
     * @return  float|int
     */
    public static function convertEuroToCent($priceInEuro)
    {
        if (empty($priceInEuro)) {
            return 0;
        } elseif (preg_match('/^(?:[^\d]*?)([-]{0,1})(\d+)(?:[,.]{0,1})(\d*)$/', $priceInEuro, $priceParts)) {
            $euros = $priceParts[2] ?: 0;
            $cents = $priceParts[3] ?: 0;

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
     * @param TokenStorage           $tokenStorage
     */
    public function __construct(EntityManagerInterface $em, TokenStorage $tokenStorage = null)
    {
        $this->em = $em;
        if ($tokenStorage && $tokenStorage->getToken()) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
        $this->repository = $this->em->getRepository(ParticipantPaymentEvent::class);
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
    public function setPrice(array $participants, $value, string $description)
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
                    $participant->setPrice($value);
                    $em->persist($event);
                    $em->flush($event); //ensure events are on db before fetching for to pay cache

                    $participantToPay = $this->toPayValueForParticipant($participant);
                    $participant->setToPay($participantToPay);
                    $em->persist($participant);
                    $events[] = $event;
                }
                $em->flush();
                return $events;
            }
        );
    }

    /**
     * Get all payment events for transmitted @see Participation
     *
     * @param Participation $participation Desired participation
     * @return array|ParticipantPaymentEvent[]
     */
    public function paymentHistoryForParticipation(Participation $participation)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->innerJoin('a.participation', 'p')
           ->andWhere($qb->expr()->eq('p.pid', ':pid'))
           ->orderBy('e.createdAt', 'DESC');
        $result = $qb->getQuery()->execute(['pid' => $participation->getPid()]);
        return $result;
    }

    /**
     * Get all payment events for transmitted @see Participant
     *
     * @param Participant $participant Desired participant
     * @return array|ParticipantPaymentEvent[]
     */
    public function paymentHistoryForParticipant(Participant $participant)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->andWhere($qb->expr()->eq('a.aid', ':aid'))
           ->orderBy('e.createdAt', 'DESC');
        $result = $qb->getQuery()->execute(['aid' => $participant->getAid()]);
        return $result;
    }

    /**
     * Get current price for transmitted @see Participant
     *
     * @param Participant $participant Desired participant
     * @return int
     */
    public function getPriceForParticipant(Participant $participant)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e.value')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->andWhere($qb->expr()->eq('a.aid', ':aid'))
            ->andWhere('e.isPriceSet = 1')
           ->orderBy('e.createdAt', 'DESC')
           ->setMaxResults(1);

        $result = $qb->getQuery()->execute(['aid' => $participant->getAid()]);
        if (is_array($result) && isset($result[0]) && isset($result[0]['value'])) {
            return $result[0]['value'];
        } else {
            return null;
        }
    }

    /**
     * Get all payment events for transmitted @see Participants
     *
     * @param array|Participant[] $participants List of Participants
     * @return array|ParticipantPaymentEvent[]
     */
    public function paymentHistoryForParticipantList(array $participants)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->orderBy('e.createdAt', 'DESC');
        /** @var Participant $participant */
        foreach ($participants as $participant) {
            $qb->orWhere($qb->expr()->eq('e.participant', $participant->getAid()));
        }
        $result = $qb->getQuery()->execute();
        return $result;
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
    public function paymentForParticipants(array $participantsUnordered, $value, string $description)
    {
        $participants = [];
        /** @var Participant $participant */
        foreach ($participantsUnordered as $participant) {
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
                    $toPay = $this->toPayValueForParticipant($participant, false);
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

                    $participantToPay = $this->toPayValueForParticipant($participant);
                    $participant->setToPay($participantToPay);

                    $this->em->persist($participant);
                    $payments[] = $payment;
                }
                $this->em->flush();
                $this->updatePaidStatus($participation);

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
    public function paymentForParticipant(Participant $participant, $value, string $description)
    {
        return $this->em->transactional(
            function () use ($participant, $value, $description) {
                $payment = ParticipantPaymentEvent::createPaymentEvent($this->user, $value, $description);
                $participant->addPaymentEvent($payment);

                $this->em->persist($payment);
                $this->em->flush($payment);

                $participantToPay = $this->toPayValueForParticipant($participant);
                $participant->setToPay($participantToPay);


                $participation = $participant->getParticipation();
                $this->updatePaidStatus($participation);

                $this->em->flush();
                return $payment;
            }
        );
    }

    /**
     * Get amount of money which still needs to be paid for a single @see Participant
     *
     * @param Participation $participation Target participation
     * @param bool          $inEuro        If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|null                    Value needed to be payed
     */
    public function toPayValueForParticipation(Participation $participation, $inEuro = false)
    {
        $fullHistory   = $this->paymentHistoryForParticipation($participation);
        $currentPrices = [];
        $payments      = [];
        /** @var Participant $participant */
        foreach ($participation->getParticipants() as $participant) {
            $currentPrices[$participant->getAid()] = null;
        }
        /** @var ParticipantPaymentEvent $event */
        foreach ($fullHistory as $event) {
            $aid = $event->getParticipant()->getAid();
            if ($event->isPricePaymentEvent()) {
                $payments[] = $event;
            } elseif ($event->isPriceSetEvent() && $currentPrices[$aid] === null) {
                $currentPrices[$aid] = $event->getValue();
            }
        }
        foreach ($currentPrices as $aid => $currentPrice) {
            if ($currentPrices === null) {
                //no price set event for current participant present, so default price of event is used
                $currentPrices[$aid] = $participant->getEvent()->getPrice();
            }
        }
        $allPricesNull = true;
        foreach ($currentPrices as $currentPrice) {
            if ($currentPrice !== null) {
                $allPricesNull = false;
                break;
            }
        }
        if ($allPricesNull) {
            return 0;
        }

        $currentTotalPrice = array_sum($currentPrices);

        /** @var ParticipantPaymentEvent $payment */
        foreach ($payments as $payment) {
            $currentTotalPrice += $payment->getValue();
        }

        if ($inEuro) {
            $currentTotalPrice /= 100;
        }

        return $currentTotalPrice;
    }

    /**
     * Get amount of money which still needs to be paid for a single @see Participant
     *
     * @param Participant $participant Target participant
     * @param bool        $inEuro      If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|null                Value needed to be payed
     */
    public function toPayValueForParticipant(Participant $participant, $inEuro = false)
    {
        $fullHistory  = $this->paymentHistoryForParticipant($participant);
        $currentPrice = null;
        $payments     = [];

        /** @var ParticipantPaymentEvent $event */
        foreach ($fullHistory as $event) {
            if ($event->isPricePaymentEvent()) {
                $payments[] = $event;
            } elseif ($event->isPriceSetEvent() && $currentPrice === null) {
                $currentPrice = $event->getValue();
            }
        }
        if ($currentPrice === null) {
            //no price set event for current participant present, default price of event is not used
            return null;
        }

        /** @var ParticipantPaymentEvent $payment */
        foreach ($payments as $payment) {
            $currentPrice += $payment->getValue();
        }

        if ($inEuro) {
            $currentPrice /= 100;
        }

        return $currentPrice;
    }

    /**
     * Add or remove paid status depending on if there is still something which needs to be paid
     *
     * @param Participation $participation Target participation to check
     * @throws \Throwable
     */
    private function updatePaidStatus(Participation $participation)
    {
        $isPaidExpected = ($this->toPayValueForParticipation($participation) <= 0);
        $isPaidGiven    = $participation->isPaid();

        if ($isPaidExpected !== $isPaidGiven) {
            $participation->setIsPaid($isPaidExpected);
            $this->em->persist($participation);
        }
    }

}