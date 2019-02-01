<?php

namespace AppBundle\Manager\Payment\PriceSummand\Formula;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class FormulaVariableProvider
{
    
    /**
     * em
     *
     * @var EntityManagerInterface
     */
    private $em;
    
    /**
     * All @see Attribute entities with their related options
     *
     * @var null|array|Attribute[]
     */
    private $attributes = null;
    
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
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /**
     * Get all attributes suitable for evaluation
     *
     * @return Attribute[]|array
     */
    private function getAttributes(): array
    {
        if ($this->attributes === null) {
            $this->attributes = $this->em->getRepository(Attribute::class)->findAllWithFormulaAndOptions();
        }
        return $this->attributes;
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
        foreach ($this->getAttributes() as $attribute) {
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
     * Provide all variables usable for transmitted attribute
     *
     * @param Attribute $attribute
     * @return array|FormulaVariableInterface[] List of variables
     */
    public function provideForAttribute(Attribute $attribute): array
    {
        $bid = $attribute->getBid();
        if (!isset($this->fieldVariablesCache[$bid])) {
            $variables = $this->getAttributeVariableNames($attribute);
            
            switch ($attribute->getFieldType()) {
                case ChoiceType::class:
                    $variables   = array_merge($variables, $this->getAttributeChoiceVariables($attribute));
                    $variables[] = new FormulaVariable(
                        '$choicesSelectedCount', 'Anzahl der ausgewählten Optionen', true, false
                    );
                    break;
                case NumberType::class:
                    $variables[] = new FormulaVariable('$value', 'Eingegebener Wert', true, false);
                    break;
            }
            
            $this->fieldVariablesCache[$bid] = $variables;
        }
        
        return $this->fieldVariablesCache[$bid];
    }
    
    
}