<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager;

use AppBundle\Entity\CommentBase;
use AppBundle\Entity\CommentRepositoryBase;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantComment;
use AppBundle\Entity\ParticipantPaymentEvent;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationComment;
use AppBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
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
     * CommentManager constructor.
     *
     * @param EntityManagerInterface $em
     * @param TokenStorage           $tokenStorage
     */
    public function __construct(EntityManagerInterface $em, TokenStorage $tokenStorage = null)
    {
        $this->em = $em;
        if ($tokenStorage) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
        $this->repository = $this->em->getRepository('AppBundle:ParticipantPaymentEvent');
    }

    /**
     * Set price for multiple participants
     *
     * @param array     $participants List of participants where this operation should be applied to
     * @param int|float $value        New price
     * @param string    $description  Description for change
     * @return array|ParticipantPaymentEvent[]
     */
    public function setPrice(array $participants, $value, string $description)
    {
        $em    = $this->em;
        $price = self::convertEuroToCent($value);
        /** @var Participant $participant */

        return $em->transactional(
            function (EntityManager $em) use ($participants, $price, $description) {
                $events = [];
                /** @var Participant $participant */
                foreach ($participants as $participant) {
                    $participant->setPrice($price);
                    $em->persist($participant);
                    $event = ParticipantPaymentEvent::createPriceSetEvent(
                        $this->user, $price, $description
                    );
                    $participant->addPaymentEvent($event);
                    $em->persist($event);
                    $events[] = $event;
                }
                $em->flush();
                return $events;
            }
        );
    }

    /**
     * Convert value in euro to value in euro cents
     *
     * @param   string|float|int $priceInEuro Value in euros, separated by comma
     * @return float|int
     */
    public static function convertEuroToCent($priceInEuro)
    {
        if (preg_match('/^(\d+)(?:[,.]{0,1})(\d*)$/', $priceInEuro, $priceParts)) {
            $euros = $priceParts[1] ?: 0;
            $cents = $priceParts[2] ?: 0;

            return ($euros * 100 + $cents);
        } else {
            throw new \InvalidArgumentException('Failed to convert "' . $priceInEuro . '" to euro cents');
        }
    }

    /**
     * Get all payment events for transmitted @see Participation
     *
     * @param Participation $participation Desired participation
     * @return array|ParticipantPaymentEvent[]
     */
    public function paymentHistoryForParticipation(Participation $participation) {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
            ->from(ParticipantPaymentEvent::class, 'e')
            ->innerJoin('e.participant', 'a')
            ->innerJoin('a.participation', 'p')
            ->andWhere($qb->expr()->eq('p.pid', $participation->getPid()))
            ->orderBy('e.createdAt', 'DESC');
        $result = $qb->getQuery()->execute();
        return $result;
    }

    /**
     * Get all payment events for transmitted @see Participants
     *
     * @param array|Participant[] $participants List of Participants
     * @return array|ParticipantPaymentEvent[]
     */
    public function paymentHistoryForParticipantList(array $participants) {
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
}