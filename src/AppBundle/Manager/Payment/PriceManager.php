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


use AppBundle\Entity\AcquisitionAttribute\ChoiceFilloutValue;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\EntityHavingFilloutsInterface;
use AppBundle\Manager\Payment\PriceSummand\BasePriceSummand;
use AppBundle\Manager\Payment\PriceSummand\EntityPriceTag;
use AppBundle\Manager\Payment\PriceSummand\FilloutSummand;
use AppBundle\Manager\Payment\PriceSummand\SummandImpactedInterface;
use AppBundle\Manager\Payment\PriceSummand\SummandCausableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class PriceManager
{

    /**
     * Lazy initializer @see ExpressionLanguage
     *
     * @var ExpressionLanguageProvider
     */
    protected $expressionLanguageProvider;

    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Price tag cache
     *
     * @var array
     */
    private $summandCache = [];

    /**
     * Base price cache for @see Event and @see Participant entities
     *
     * @var array|array[]
     */
    private $basePriceCache = [];

    /**
     * CommentManager constructor.
     *
     * @param EntityManagerInterface     $em
     * @param ExpressionLanguageProvider $expressionLanguageProvider
     */
    public function __construct(
        EntityManagerInterface $em,
        ExpressionLanguageProvider $expressionLanguageProvider
    ) {
        $this->em                         = $em;
        $this->expressionLanguageProvider = $expressionLanguageProvider;
    }

    /**
     * Get price for transmitted participation
     *
     * @param Participation $participation Target Participation
     * @param bool          $inEuro        If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return float|int|null
     */
    public function getPriceForParticipation(Participation $participation, $inEuro = false)
    {
        $allNull = true;
        $prices  = [];
        foreach ($participation->getParticipants() as $participant) {
            $priceTag = $this->getEntityPriceTag($participant);
            $price    = $priceTag->getPrice($inEuro);
            $prices[] = $prices;
            $allNull  = $allNull && $price === null;
        }
        if ($allNull) {
            return null;
        }
        return array_sum($prices);
    }

    /**
     * Get price for transmitted participant
     *
     * @param Participant $participant Target Participant
     * @param bool        $inEuro      If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return float|int|null
     */
    public function getPriceForParticipant(Participant $participant, $inEuro = false)
    {
        $priceTag = $this->getEntityPriceTag($participant);
        return $priceTag->getPrice($inEuro);
    }


    /**
     * Fetch all current base prices for all @see Participant of transmitted @see Event
     *
     * @param Event $event Desired Event
     * @return void
     */
    private function fetchMostRecentPricesForEvent(Event $event): void
    {
        $eid = $event->getEid();
        /** @var EventRepository $eventRepository */
        $eventRepository            = $this->em->getRepository(Event::class);
        $participants               = $eventRepository->participantAidsForEvent($event);
        $this->basePriceCache[$eid] = array_fill_keys($participants, null);

        /** @var \DateTime $start */
        $query = 'SELECT event.aid, event.price_value
                    FROM participant_payment_event event
              INNER JOIN (SELECT event_inner.aid AS aid, MAX(event_inner.created_at) AS most_recent
                            FROM participation participation_inner
                      INNER JOIN participant participant_inner ON participation_inner.pid = participant_inner.pid
                       LEFT JOIN participant_payment_event event_inner ON participant_inner.aid = event_inner.aid
                           WHERE participation_inner.eid = :eid
                             AND event_inner.is_price_set = 1
                        GROUP BY event_inner.aid
                       ) latestdata
                      ON (event.aid = latestdata.aid
                          AND event.created_at = latestdata.most_recent)';

        $result = $this->em->getConnection()->executeQuery($query, ['eid' => $eid]);

        while ($row = $result->fetch()) {
            $this->basePriceCache[$eid][((int)$row['aid'])] = (int)$row['price_value'];
        }
    }

    /**
     * Get current price for transmitted @see Participant
     *
     * @param Participant $participant Desired participant
     * @return int
     */
    private function getMostRecentBasePriceForParticipant(Participant $participant)
    {
        $eid = $participant->getEvent()->getEid();
        if (!isset($this->basePriceCache[$eid])) {
            $this->fetchMostRecentPricesForEvent($participant->getEvent());
        }
        return $this->basePriceCache[$eid][$participant->getAid()];
    }

    /**
     * Get summands for calculating price of participant
     *
     * @param SummandImpactedInterface      $impactedEntity Either @see Participant, or @see Employee
     * @param SummandCausableInterface|null $causingEntity  Either @see Participant, @see Participation or @see Employee
     * @return array
     */
    private function getEntitySummands(
        SummandImpactedInterface $impactedEntity,
        SummandCausableInterface $causingEntity = null
    ): array {
        if (!$causingEntity) {
            $causingEntity = $impactedEntity;
        }

        if (!isset($this->summandCache[get_class($impactedEntity)])
            || !isset($this->summandCache[get_class($impactedEntity)][$impactedEntity->getId()])) {

            $summands = [];
            if ($causingEntity instanceof Participant) {
                $price = $this->getMostRecentBasePriceForParticipant($causingEntity);
                if ($price !== null) {
                    $summands[0] = new BasePriceSummand($causingEntity);
                }
                $participation = $causingEntity->getParticipation();
                $participation->getId();
                $summands = array_merge(
                    $summands, $this->getEntitySummands($impactedEntity, $participation)
                );
            }

            if ($causingEntity instanceof EntityHavingFilloutsInterface) {
                /** @var Fillout $fillout */
                foreach ($causingEntity->getAcquisitionAttributeFillouts() as $fillout) {
                    $attribute = $fillout->getAttribute();
                    $bid       = $attribute->getBid();
                    if (!$attribute->isPriceFormulaEnabled()) {
                        continue;
                    }
                    $value = $fillout->getValue();
                    if ($attribute->getFieldType() === ChoiceType::class) {
                        if (!$value instanceof ChoiceFilloutValue) {
                            throw new \UnexpectedValueException(
                                'Expecting ChoiceFilloutValue when attribute is ChoiceType'
                            );
                        }
                    } elseif ($attribute->getPriceFormula()) {
                        $summand = $this->filloutSummand($impactedEntity, $fillout);
                        if ($summand) {
                            $summands[$bid] = $summand;
                        }
                    }
                }
            }
            $this->summandCache[get_class($causingEntity)][$causingEntity->getId()] = $summands;
        }
        return $this->summandCache[get_class($causingEntity)][$causingEntity->getId()];
    }

    /**
     * Get summands for calculating price of participant
     *
     * @param SummandImpactedInterface $impactedEntity Either @see Participant, or @see Employee
     * @return EntityPriceTag
     */
    public function getEntityPriceTag(SummandImpactedInterface $impactedEntity)
    {
        return new EntityPriceTag($impactedEntity, $this->getEntitySummands($impactedEntity));
    }


    /**
     * Generate fillout summand for transmitted @see Fillout
     *
     * @param SummandImpactedInterface $entity
     * @param Fillout                  $fillout
     * @return FilloutSummand|null
     */
    private function filloutSummand(SummandImpactedInterface $entity, Fillout $fillout)
    {
        $attribute = $fillout->getAttribute();
        $formula   = $attribute->getPriceFormula();
        $value     = $fillout->getValue();
        if (!$formula) {
            return null;
        }
        $values = [];
        if ($attribute->getFieldType() === NumberType::class) {
            $values['value'] = $value->getTextualValue();
        }

        return new FilloutSummand(
            $entity,
            $fillout,
            0 //$this->expressionLanguage()->evaluate($formula, $values) TODO
        );
    }

    /**
     * Get cached @see ExpressionLanguage
     *
     * @return ExpressionLanguage
     */
    private function expressionLanguage(): ExpressionLanguage
    {
        return $this->expressionLanguageProvider->provide();
    }

}
