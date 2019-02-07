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


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\ChoiceFilloutValue;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\EntityHavingFilloutsInterface;
use AppBundle\Manager\Payment\PriceSummand\BasePriceSummand;
use AppBundle\Manager\Payment\PriceSummand\EntityPriceTag;
use AppBundle\Manager\Payment\PriceSummand\FilloutSummand;
use AppBundle\Manager\Payment\PriceSummand\Formula\AttributeFormulaVariable;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableInterface;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableProvider;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableResolver;
use AppBundle\Manager\Payment\PriceSummand\SummandImpactedInterface;
use AppBundle\Manager\Payment\PriceSummand\SummandCausableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

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
     * Cache for @see Attribute entities from database
     *
     * @var array|null|Attribute[]
     */
    private $attributesCache = null;
    
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
     * Cache for calculated @see FilloutSummand objects
     *
     * @var array|array[]
     */
    private $attributeFormulaResultCache = [];

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
        $impactedEntityId = $impactedEntity->getId();

        if (!isset($this->summandCache[get_class($impactedEntity)])
            || !isset($this->summandCache[get_class($impactedEntity)][$impactedEntityId])) {

            $summands = [];
            if ($causingEntity instanceof Participant) {
                $price = $this->getMostRecentBasePriceForParticipant($causingEntity);
                if ($price !== null) {
                    $summands[0] = new BasePriceSummand($causingEntity);
                }
                $participation = $causingEntity->getParticipation();
                $summands = array_merge($summands, $this->getEntitySummands($impactedEntity, $participation));
            }
    
            if ($causingEntity instanceof EntityHavingFilloutsInterface) {
                $event = $causingEntity->getEvent();
                /** @var Attribute $eventAttribute */
                foreach ($event->getAcquisitionAttributes(
                    $causingEntity instanceof Participation,
                    $causingEntity instanceof Participant,
                    $causingEntity instanceof Employee,
                    true,
                    true
                ) as $attribute) {
                    $bid     = $attribute->getBid();
                    $fillout = $causingEntity->getAcquisitionAttributeFillout($bid, true);
                    if (!$attribute->isPriceFormulaEnabled() && $attribute->getPriceFormula()) {
                        continue;
                    }
                    $summand = $this->filloutSummand($fillout, $impactedEntity);
                    if ($summand) {
                        $summands[$bid] = $summand;
                    }
                }
            }
            $this->summandCache[get_class($impactedEntity)][$impactedEntityId] = $summands;
        }
        return $this->summandCache[get_class($impactedEntity)][$impactedEntityId];
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
     * Resolve transmitted variable for fillout and impacted entity
     *
     * @param FormulaVariableInterface $variable       Variable to resolve
     * @param Fillout                  $fillout        Current fillout having validity for field requiring the variable
     * @return int|float
     */
    private function resolveVariable(
        FormulaVariableInterface $variable,
        Fillout $fillout
    ) {
        $name         = $variable->getName();
        $filloutValue = $fillout->getValue();
    
        if ($name === FormulaVariableProvider::VARIABLE_VALUE) {
            return (float)$filloutValue->getTextualValue();
        } elseif ($name === FormulaVariableProvider::VARIABLE_VALUE_NOT_EMPTY) {
            return !empty($fillout->getValue()->getTextualValue());
        } elseif ($name === FormulaVariableProvider::VARIABLE_CHOICE_SELECTED_COUNT) {
            if ($fillout->getAttribute()->getFieldType() !== ChoiceType::class ||
                !$filloutValue instanceof ChoiceFilloutValue) {
                throw new \InvalidArgumentException('Using choice count variable in non-choice attribute');
            }
            return count($filloutValue->getSelectedChoices());
        } elseif ($variable instanceof AttributeFormulaVariable) {
            //only one if the three
            $filloutEmployee      = $fillout->getEmployee();
            $filloutParticipant   = $fillout->getParticipant();
            $filloutParticipation = $fillout->getParticipation();

            $event            = $fillout->getEvent();
            $relatedAttribute = $variable->getAttribute();
            if (!$relatedAttribute->isPriceFormulaEnabled()) {
                return 0; //related formula does not have formula enabled (no more)
            }
            foreach ($event->getAcquisitionAttributes(true, true, true, true, true) as $attribute) {
                if ($relatedAttribute->getBid() === $attribute->getBid()) {
                    //related attribute is also assigned to this event, so calculate

                    if ($relatedAttribute->isUseAtEmployee() && $filloutEmployee) {
                        return $this->getValueFor($filloutEmployee, $relatedAttribute);
                    }
                    if ($relatedAttribute->getUseAtParticipant() && $filloutParticipant) {
                        return $this->getValueFor($filloutParticipant, $relatedAttribute);
                    }
                    if ($relatedAttribute->getUseAtParticipation()) {
                        if ($filloutParticipation) {
                            return $this->getValueFor($filloutParticipation, $relatedAttribute);
                        } elseif ($filloutParticipant) {
                            return $this->getValueFor($filloutParticipant->getParticipation(), $relatedAttribute);
                        }
                    }
                    return 0;
                    //related formula is calculated at employee but fillout is related
                    //to participant/participation or vice versa
                }
            }
            return 0; //related attribute is not assigned to this @see Event
        }
    }
    
    /**
     * Iterate @see Fillout for transmitted @see $entity until finding fillout for transmitted @see Attribute,
     * then returning relateds value
     *
     * @param EntityHavingFilloutsInterface $entity
     * @param Attribute $relatedAttribute
     * @return float|int
     */
    private function getValueFor(EntityHavingFilloutsInterface $entity, Attribute $relatedAttribute)
    {
        $bid = $relatedAttribute->getBid();
        $id  = $entity->getId();
        if (!isset($this->attributeFormulaResultCache[$bid])
        ) {
            $this->attributeFormulaResultCache[$bid] = [];
        }
        if (!array_key_exists($id, $this->attributeFormulaResultCache[$bid])) {
            $result     = 0;
            $attributes = $this->em->getRepository(Attribute::class)->findAllWithFormulaAndOptions();
            
            foreach ($entity->getAcquisitionAttributeFillouts() as $relatedFillout) {
                if ($relatedFillout->getAttribute()->getBid() === $bid) {
                    $formula = $relatedAttribute->getPriceFormula();
                    
                    if ($formula) {
                        
                        $resolver = new FormulaVariableResolver($this->expressionLanguageProvider, $attributes);
                        $used     = $resolver->getUsedVariables($relatedAttribute);
                        $values   = [];
                        foreach ($used as $variable) {
                            $values[$variable->getName()] = $this->resolveVariable(
                                $variable, $relatedFillout
                            );
                        }
                        
                        $result = $this->expressionLanguage()->evaluate($formula, $values);
                        break;
                    }
                }
            }
            $this->attributeFormulaResultCache[$bid][$id] = $result;
        }
        
        return $this->attributeFormulaResultCache[$bid][$id];
    }
    
    
    /**
     * Generate fillout summand for transmitted @see Fillout
     *
     * @param Fillout $fillout
     * @param SummandImpactedInterface $impactedEntity
     * @return FilloutSummand|null
     */
    private function filloutSummand(
        Fillout $fillout,
        SummandImpactedInterface $impactedEntity
    )
    {
        $attribute = $fillout->getAttribute();
        $formula   = $attribute->getPriceFormula();
        
        if (!$formula) {
            return null;
        }
        $resolver = new FormulaVariableResolver($this->expressionLanguageProvider, $this->attributes());
        $used     = $resolver->getUsedVariables($attribute);
        $values   = [];
        foreach ($used as $variable) {
            $values[$variable->getName()] = $this->resolveVariable(
                $variable, $fillout
            );
        }
        $result = $this->expressionLanguage()->evaluate($formula, $values);
        return new FilloutSummand($impactedEntity, $fillout, $result);
    }
    
    /**
     * Fetch cached attributes
     *
     * @return array|Attribute[]
     */
    private function attributes(): array
    {
        if ($this->attributesCache === null) {
            $this->attributesCache = $this->em->getRepository(Attribute::class)->findAllWithFormulaAndOptions();
        }
        return $this->attributesCache;
    }
    
    /**
     * Fetch @see Attribute by transmitted bid (cached)
     *
     * @param int $bid ID
     * @return Attribute Entitiy
     */
    private function attribute(int $bid): Attribute
    {
        $attributes = $this->attributes();
        return $attributes[$bid];
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
