<?php

namespace AppBundle\Manager\Payment\PriceSummand\Formula;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class FormulaVariableProvider implements FormulaVariableProviderInterface
{

    /**
     * All @see Attribute entities with their related options
     *
     * @var array|Attribute[]
     */
    private $attributes;

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
     * @param array|Attribute[] $attributes Related attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
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
            foreach ($this->getAttributeVariableNames($attribute) as $variable) {
                $this->addFieldVariableToCache($variable, $bid);
            }

            switch ($attribute->getFieldType()) {
                case ChoiceType::class:
                    foreach ($this->getAttributeChoiceVariables($attribute) as $variable) {
                        $this->addFieldVariableToCache($variable, $bid);
                    }
                    $this->addFieldVariableToCache(
                        new FormulaVariable('choicesSelectedCount', 'Anzahl der ausgewählten Optionen', true, false),
                        $bid
                    );
                    break;
                case NumberType::class:
                    $this->addFieldVariableToCache(
                        new FormulaVariable('value', 'Eingegebener Wert', true, false), $bid
                    );
                    break;
            }
        }

        return $this->fieldVariablesCache[$bid];
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
