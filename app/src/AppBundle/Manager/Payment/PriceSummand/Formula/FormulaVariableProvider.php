<?php

namespace AppBundle\Manager\Payment\PriceSummand\Formula;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as FormChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType as FormNumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as FormTextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType as FormDateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as FormDateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType as FormTextType;

class FormulaVariableProvider implements FormulaVariableProviderInterface
{

    const VARIABLE_CHOICE_SELECTED_COUNT = 'choicesSelectedCount';

    const VARIABLE_VALUE = 'value';

    const VARIABLE_VALUE_NOT_EMPTY = 'valueNotEmpty';

    /**
     * All {@see Attribute} entities with their related options
     *
     * @var array|Attribute[]
     */
    private $attributes;
    
    /**
     * All {@see EventSpecificVariable} entities
     *
     * @var array|EventSpecificVariable[]
     */
    private $eventVariables;

    /**
     * Caches all variables which can be used in related field
     *
     * @var array
     */
    private $fieldVariablesCache = [];

    /**
     * attributeVariableCache
     *
     * @var array|FormulaVariableInterface[]
     */
    private $attributeVariableCache = [];

    /**
     * choiceVariableCache
     *
     * @var array|FormulaVariableInterface[]
     */
    private $choiceVariableCache = [];
    
    /**
     * FormulaVariableProvider constructor.
     *
     * @param array|Attribute[] $attributes                 Related attributes
     * @param array|EventSpecificVariable[] $eventVariables All {@see EventSpecificVariable} entities
     */
    public function __construct(array $attributes, array $eventVariables)
    {
        $this->attributes     = $attributes;
        $this->eventVariables = $eventVariables;
    }

    /**
     * Provide all variables usable for transmitted attribute
     *
     * @param Attribute $attribute
     * @return array|FormulaVariableInterface[] List of variables
     */
    public function variables(Attribute $attribute): array
    {
        $bid = $attribute->getBid();
        if (!isset($this->fieldVariablesCache[$bid])) {
            $this->fieldVariablesCache[$bid] = [];
    
            foreach ($this->eventVariables as $variable) {
                $this->addFieldVariableToCache($variable, $bid);
            }
            
            foreach ($this->getAttributeVariableNames($attribute) as $variable) {
                $this->addFieldVariableToCache($variable, $bid);
            }
            
            switch ($attribute->getFieldType()) {
                case FormChoiceType::class:
                    foreach ($this->getAttributeChoiceVariables($attribute) as $variable) {
                        $this->addFieldVariableToCache($variable, $bid);
                    }
                    $this->addFieldVariableToCache(
                        new FormulaVariable(self::VARIABLE_CHOICE_SELECTED_COUNT, 'Anzahl der ausgewählten Optionen', true, false),
                        $bid
                    );
                    break;
                case FormNumberType::class:
                    $this->addFieldVariableToCache(
                        new FormulaVariable(self::VARIABLE_VALUE, 'Eingegebener Wert', true, false), $bid
                    );
                    break;
            }
            
            if ($attribute->getFieldType() !== FormChoiceType::class && !$attribute->isRequired()) {
                $this->addFieldVariableToCache(
                    new FormulaVariable(self::VARIABLE_VALUE_NOT_EMPTY, 'Ist ein Wert eingegeben?', false, true), $bid
                );
            }
        }
        return $this->fieldVariablesCache[$bid];
    }

    /**
     * Get variables array having test values assigned
     *
     * @param array|FormulaVariableInterface[] Variables to provide test data for
     * @return array|mixed[] Result
     */
    public static function getTestVariableValues(array $variables): array
    {
        $result = [];
        foreach ($variables as $variable) {
            $value = null;
            if ($variable->isBoolean()) {
                $value = true;
            }
            if ($variable->isNummeric()) {
                $value = 1;
            }
            $result[$variable->getName()] = $value;
        }
        return $result;
    }

    /**
     * Get all variables for other fields except transmitted one
     *
     * @param Attribute $excludeAttribute Attribute to exclude
     * @return array|FormulaVariableInterface[] List of variables
     */
    private function getAttributeVariableNames(Attribute $excludeAttribute): array
    {
        $variables = [];
        foreach ($this->attributes as $attribute) {
            $bid = $attribute->getBid();
            if ($attribute->getBid() !== $excludeAttribute->getBid()) {
                if (!isset($this->attributeVariableCache[$bid])) {
                    $this->attributeVariableCache[$bid] = new AttributeFormulaVariable($attribute);
                }
                $variables[] = $this->attributeVariableCache[$bid];
            }
        }
        return $variables;
    }

    /**
     * Get all variables for all choices of transmitted attribute
     *
     * @param Attribute $attribute Attribute to use related choices
     * @return array|FormulaVariableInterface[] List of variables
     */
    private function getAttributeChoiceVariables(Attribute $attribute): array
    {
        $variables = [];
        /** @var AttributeChoiceOption $choice */
        foreach ($attribute->getChoiceOptions() as $choice) {
            $id = $choice->getId();
            if (!isset($this->choiceVariableCache[$id])) {
                $this->choiceVariableCache[$id] = new AttributeChoiceFormulaVariable($choice);
            }
            $variables[] = $this->choiceVariableCache[$id];
        }
        return $variables;
    }

    /**
     * Adds variable to cache for bids @see Attribute, throws exception if already defined
     *
     *
     * @param FormulaVariableInterface $variable Variable to add
     * @param int                      $bid      Related @see Attribute id
     */
    private function addFieldVariableToCache(FormulaVariableInterface $variable, int $bid)
    {
        $name = $variable->getName();
        if (isset($this->fieldVariablesCache[$bid][$name])) {
            throw new \RuntimeException('Provided variable name is already used');
        }
        $this->fieldVariablesCache[$bid][$name] = $variable;
    }

}
