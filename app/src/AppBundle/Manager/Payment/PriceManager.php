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
use AppBundle\Entity\AcquisitionAttribute\Formula\CalculationImpossibleException;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use AppBundle\Entity\AcquisitionAttribute\Variable\NoDefaultValueSpecifiedException;
use AppBundle\Entity\CustomField\ChoiceCustomFieldValue;
use AppBundle\Entity\CustomField\CustomFieldValueInterface;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Manager\Payment\PriceSummand\BasePriceSummand;
use AppBundle\Manager\Payment\PriceSummand\CustomFieldValueSummand;
use AppBundle\Manager\Payment\PriceSummand\EntityPriceTag;
use AppBundle\Manager\Payment\PriceSummand\Formula\AttributeChoiceFormulaVariable;
use AppBundle\Manager\Payment\PriceSummand\Formula\AttributeFormulaVariable;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableInterface;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableProvider;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableResolver;
use AppBundle\Manager\Payment\PriceSummand\SummandCausableInterface;
use AppBundle\Manager\Payment\PriceSummand\SummandImpactedInterface;
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
     * @var FormulaVariableResolver|null
     */
    private $resolver;

    /**
     * Cache for @see Attribute entities from database
     *
     * @var array|null|Attribute[]
     */
    private $attributesCache = null;

    /**
     * Cache for {@see EventSpecificVariable} entities from database
     *
     * @var array|null|EventSpecificVariable[]
     */
    private $eventVariablesCache = null;

    /**
     * Cache for values for event specific variables
     *
     * @var array|array[]
     */
    private $eventVariableValueCache = [];

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
     * Cache for calculated @see customFieldValueSummand objects
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
        EntityManagerInterface     $em,
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

        /** @var Participant $participant */
        foreach ($participation->getParticipants() as $participant) {
            if ($participant->isWithdrawn() || $participant->isRejected() || $participant->getDeletedAt()) {
                continue; //do not take into account
            }
            $priceTag = $this->getEntityPriceTag($participant);
            $price    = $priceTag->getPrice($inEuro);
            $prices[] = $price;
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
     * Fetch all current base prices for all @param Event $event Desired Event
     *
     * @return void
     * @see Participant of transmitted @see Event
     *
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
     * Get current price for transmitted @param Participant $participant Desired participant
     *
     * @return int
     * @see Participant
     *
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
                $summands      = array_merge($summands, $this->getEntitySummands($impactedEntity, $participation));
            }

            if ($causingEntity instanceof EntityHavingCustomFieldValueInterface) {
                $event = $causingEntity->getEvent();
                /** @var Attribute $eventAttribute */
                foreach ($event->getAcquisitionAttributes(
                    $causingEntity instanceof Participation,
                    $causingEntity instanceof Participant,
                    $causingEntity instanceof Employee,
                    true,
                    true
                ) as $attribute) {
                    if (!$attribute->isPriceFormulaEnabled() || !$attribute->getPriceFormula()) {
                        continue;
                    }
                    $bid                        = $attribute->getBid();
                    $customFieldValueCollection = $causingEntity->getCustomFieldValues();
                    $customFieldValueContainer  = $customFieldValueCollection->getByCustomField($attribute);
                    $customFieldValue           = $customFieldValueContainer->getValue();
                    $summand                    = $this->customFieldValueSummand(
                        $causingEntity, $attribute, $customFieldValue, $impactedEntity
                    );
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
     * Resolve transmitted variable for custom field, custom field value and impacted entity
     *
     * @param FormulaVariableInterface  $variable Variable to resolve
     * @param Attribute                 $customField
     * @param CustomFieldValueInterface $customFieldValue
     * @param SummandCausableInterface  $causingEntity
     * @return int|float|bool
     */
    private function resolveVariable(
        FormulaVariableInterface  $variable,
        Attribute                 $customField,
        CustomFieldValueInterface $customFieldValue,
        SummandCausableInterface  $causingEntity
    ) {
        $name  = $variable->getName();
        $event = $causingEntity->getEvent();

        if ($name === FormulaVariableProvider::VARIABLE_VALUE) {
            return (float)$customFieldValue->getTextualValue();
        } elseif ($name === FormulaVariableProvider::VARIABLE_VALUE_NOT_EMPTY) {
            return !empty($customFieldValue->getTextualValue());
        } elseif ($name === FormulaVariableProvider::VARIABLE_CHOICE_SELECTED_COUNT) {
            if ($customField->getFieldType() !== ChoiceType::class ||
                !$customFieldValue instanceof ChoiceCustomFieldValue) {
                throw new \InvalidArgumentException('Using choice count variable in non-choice attribute');
            }
            return count($customFieldValue->getSelectedChoices());
        } elseif ($variable instanceof AttributeChoiceFormulaVariable) {
            if ($customField->getFieldType() !== ChoiceType::class ||
                !$customFieldValue instanceof ChoiceCustomFieldValue) {
                throw new \InvalidArgumentException('Using choice count variable in non-choice attribute');
            }
            $expectedChoiceId = $variable->getChoice()->getId();

            foreach ($customFieldValue->getSelectedChoices() as $selectedChoiceId) {
                if ($expectedChoiceId === $selectedChoiceId) {
                    return true;
                }
            }
            return false;
        } elseif ($variable instanceof AttributeFormulaVariable) {
            $relatedAttribute = $variable->getAttribute();
            if (!$relatedAttribute->isPriceFormulaEnabled()) {
                return 0; //related formula does not have formula enabled (no more)
            }
            foreach ($event->getAcquisitionAttributes(true, true, true, true, true) as $attribute) {
                if ($relatedAttribute->getBid() === $attribute->getBid()) {
                    //related attribute is also assigned to this event, so calculate

                    if ($relatedAttribute->getUseAtEmployee() && $causingEntity instanceof Employee) {
                        return $this->getValueFor($causingEntity, $relatedAttribute);
                    }
                    if ($relatedAttribute->getUseAtParticipant() && $causingEntity instanceof Participant) {
                        return $this->getValueFor($causingEntity, $relatedAttribute);
                    }
                    if ($relatedAttribute->getUseAtParticipation()) {
                        if ($causingEntity instanceof Participation) {
                            return $this->getValueFor($causingEntity, $relatedAttribute);
                        } elseif ($causingEntity instanceof Participant) {
                            return $this->getValueFor($causingEntity->getParticipation(), $relatedAttribute);
                        }
                    }
                    return 0;
                    //related formula is calculated at employee but custom field value is related
                    //to participant/participation or vice versa
                }
            }
            return 0; //related attribute is not assigned to this @see Event
        } elseif ($variable instanceof EventSpecificVariable) {
            return $this->getValueForEventVariable($variable, $event);
        }
        throw new \InvalidArgumentException('Unknown variable type ' . get_class($variable));
    }

    /**
     * Get the (cached) value for the transmitted event for a event specific variable
     *
     * @param EventSpecificVariable $variable Variable
     * @param Event                 $event    Related event
     * @return float|int
     */
    private function getValueForEventVariable(EventSpecificVariable $variable, Event $event)
    {
        if (!isset($this->eventVariableValueCache[$variable->getId()])) {
            $this->eventVariableValueCache[$variable->getId()] = [];
        }
        if (!isset($this->eventVariableValueCache[$variable->getId()][$event->getEid()])) {
            try {
                $value = $variable->getValue($event, true);
            } catch (NoDefaultValueSpecifiedException $e) {
                throw CalculationImpossibleException::create($variable, $e);
            }

            $this->eventVariableValueCache[$variable->getId()][$event->getEid()] = $value->getValue();
        }

        return $this->eventVariableValueCache[$variable->getId()][$event->getEid()];
    }

    /**
     * Extract value
     *
     * @param EntityHavingCustomFieldValueInterface $entity
     * @param Attribute                             $customField
     * @return float|int
     */
    private function getValueFor(EntityHavingCustomFieldValueInterface $entity, Attribute $customField)
    {
        $bid = $customField->getBid();
        $id  = $entity->getId();
        if (!isset($this->attributeFormulaResultCache[$bid])
        ) {
            $this->attributeFormulaResultCache[$bid] = [];
        }
        if (!array_key_exists($id, $this->attributeFormulaResultCache[$bid])) {
            $result = 0;

            $customFieldValueContainer = $entity->getCustomFieldValues()->getByCustomField($customField);
            $customFieldValue          = $customFieldValueContainer->getValue();
            $formula                   = $customField->getPriceFormula();
            if ($formula) {
                $used   = $this->resolver()->getUsedVariables($customField);
                $values = [];
                foreach ($used as $variable) {
                    if (!$entity instanceof SummandCausableInterface) {
                        throw new \RuntimeException('Unexpected entity occurred');
                    }
                    $values[$variable->getName()] = $this->resolveVariable(
                        $variable, $customField, $customFieldValue, $entity
                    );
                }

                $result = $this->expressionLanguage()->evaluate($formula, $values);
            }
            $this->attributeFormulaResultCache[$bid][$id] = $result;
        }

        return $this->attributeFormulaResultCache[$bid][$id];
    }


    /**
     * Generate custom field value summand for transmitted
     *
     * @param SummandCausableInterface  $causingEntity
     * @param Attribute                 $customField
     * @param CustomFieldValueInterface $customFieldValue
     * @param SummandImpactedInterface  $impactedEntity
     * @return CustomFieldValueSummand|null
     */
    private function customFieldValueSummand(
        SummandCausableInterface  $causingEntity,
        Attribute                 $customField,
        CustomFieldValueInterface $customFieldValue,
        SummandImpactedInterface  $impactedEntity
    ): ?CustomFieldValueSummand {
        $formula = $customField->getPriceFormula();

        if (!$formula) {
            return null;
        }
        $used   = $this->resolver()->getUsedVariables($customField);
        $values = [];
        foreach ($used as $variable) {
            $values[$variable->getName()] = $this->resolveVariable(
                $variable, $customField, $customFieldValue, $causingEntity
            );
        }
        $result = $this->expressionLanguage()->evaluate($formula, $values);
        return new CustomFieldValueSummand($causingEntity, $impactedEntity, $customField, $customFieldValue, $result);
    }

    /**
     * Fetch cached attributes
     *
     * @return array|Attribute[]
     */
    public function attributesWithFormula(): array
    {
        if ($this->attributesCache === null) {
            $this->attributesCache = $this->em->getRepository(Attribute::class)->findAllWithFormulaAndOptions();
        }
        return $this->attributesCache;
    }

    /**
     * Fetch cached variables
     *
     * @return array|EventSpecificVariable[]
     */
    private function eventVariables(): array
    {
        if ($this->eventVariablesCache === null) {
            $this->eventVariablesCache = $this->em->getRepository(EventSpecificVariable::class)->findAll();
        }
        return $this->eventVariablesCache;
    }

    /**
     * Fetch @param int $bid ID
     *
     * @return Attribute Entitiy
     * @see Attribute by transmitted bid (cached)
     *
     */
    private function attribute(int $bid): Attribute
    {
        $attributes = $this->attributesWithFormula();
        return $attributes[$bid];
    }

    /**
     * Get cached @return ExpressionLanguage
     *
     * @see ExpressionLanguage
     *
     */
    private function expressionLanguage(): ExpressionLanguage
    {
        return $this->expressionLanguageProvider->provide();
    }

    /**
     * Provide cached formula variable resolver
     *
     * @return FormulaVariableResolver
     */
    public function resolver(): FormulaVariableResolver
    {
        if (!$this->resolver) {
            $this->resolver = new FormulaVariableResolver(
                $this->expressionLanguageProvider, $this->attributesWithFormula(), $this->eventVariables()
            );
        }
        return $this->resolver;
    }
}
