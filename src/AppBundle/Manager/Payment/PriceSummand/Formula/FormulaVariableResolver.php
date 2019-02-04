<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Payment\PriceSummand\Formula;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Manager\Payment\ExpressionLanguageProvider;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

class FormulaVariableResolver
{

    /**
     * All @see Attribute entities with their related options
     *
     * @var array|Attribute[]
     */
    private $attributes;

    /**
     * Variable provider
     *
     * @var FormulaVariableProvider
     */
    private $variableProvider;

    /**
     * Lazy initializer @see ExpressionLanguage
     *
     * @var ExpressionLanguageProvider
     */
    protected $expressionLanguageProvider;

    /**
     * Provides list of @see Atrribute ids each field depends on
     *
     * @var array|int[]
     */
    private $fieldDependencies = [];

    /**
     * FormulaVariableResolver constructor.
     *
     * @param ExpressionLanguageProvider $expressionLanguageProvider
     * @param array|Attribute[]          $attributes Attributes
     */
    public function __construct(
        ExpressionLanguageProvider $expressionLanguageProvider,
        array $attributes
    ) {
        $this->attributes                 = $attributes;
        $this->variableProvider           = new FormulaVariableProvider($attributes);
        $this->expressionLanguageProvider = $expressionLanguageProvider;
    }

    /**
     * Get textual names of used variables
     *
     * @param Attribute $attribute Attribute
     * @return array|string[]
     */
    private function getUsedFieldVariables(Attribute $attribute)
    {
        $available = $this->variableProvider->variables($attribute);
        $formula   = $attribute->getPriceFormula();
        $parsed    = $this->expressionLanguage()->parse($formula === null ? '0' : $formula, array_keys($available));

        return $this->getUsedVariableNames($parsed->getNodes());
    }

    /**
     * Provides expression language
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionLanguage
     */
    public function expressionLanguage()
    {
        return $this->expressionLanguageProvider->provide();
    }

    /**
     * Provides list of used variables for transmitted @see Node
     *
     * @param Node $node An @see ExpressionLanguage Node
     * @return array|string[] List of all used variable names
     */
    private function getUsedVariableNames(Node $node)
    {
        $used = [];
        foreach ($node->attributes as $nodeAttribute) {
            $used[] = $nodeAttribute;
        }
        foreach ($node->nodes as $node) {
            if ($node instanceof NameNode) {
                foreach ($node->attributes as $nodeAttribute) {
                    $used[] = $nodeAttribute;
                }
            }
            $used = array_merge($this->getUsedVariableNames($node), $used);
        }
        return array_unique($used);
    }

    /**
     * Determine if dependencies for transmitted @see Attribute bid are calculated
     *
     * @param int $bid Targed @see Attribute bid
     * @return bool
     */
    private function dependenciesForAttributeCalculated(int $bid): bool
    {
        return isset($this->fieldDependencies[$bid]);
    }

    /**
     * Calculate dependencies for transmitted attribute and add them to dependency list
     *
     * @param Attribute $target Attribute to get dependencies of
     */
    private function calculateDependenciesForAttribute(Attribute $target)
    {
        $bid = $target->getBid();
        if (!$this->dependenciesForAttributeCalculated($bid)) {
            $this->fieldDependencies[$bid] = [];
            $usedNames                     = $this->getUsedFieldVariables($target);
            foreach ($usedNames as $usedName) {
                if (preg_match(
                    '/' . Attribute::FORMULA_VARIABLE_PREFIX . '(?P<bid>\d+)/', $usedName, $nameInfo
                )) {
                    $dependOnBid = (int)$nameInfo['bid'];

                    $this->fieldDependencies[$bid][$dependOnBid] = $dependOnBid;
                    if (!$this->dependenciesForAttributeCalculated($dependOnBid)) {
                        //dependencies of dependant field are also required
                        $this->calculateDependenciesForAttribute($this->attributes[$dependOnBid]);
                    }
                }
            }
        }
    }

    /**
     * Get all attributes transmitted @see Attribute is directly dependant on
     *
     * @param Attribute $target Target attribute
     * @return array|Attribute[] List of attributes the transmitted one is dependant on
     */
    public function getDependenciesFor(Attribute $target): array
    {
        $bid = $target->getBid();
        if (!$this->dependenciesForAttributeCalculated($bid)) {
            $this->calculateDependenciesForAttribute($target);
            $this->validateDependencies();
        }

        $attributes = [];
        foreach ($this->fieldDependencies[$bid] as $dependantBid) {
            $attributes[] = $this->attributes[$dependantBid];
        }

        return $attributes;
    }

    /**
     * Get list of all @see Attribute entities which are depending on transmitted @see Attribute
     *
     * @param Attribute $target Attribute for dependency check
     * @return array|Attribute[] List of attributes depending on transmitted one
     */
    public function getAllDependingOn(Attribute $target): array
    {
        $targetBid = $target->getBid();

        $attributes = [];
        /** @var Attribute $attribute */
        foreach ($this->attributes as $attribute) {
            /** @var Attribute $dependency */
            foreach ($this->getDependenciesFor($attribute) as $dependency) {
                $dependencyBid = $dependency->getBid();
                if ($dependencyBid === $targetBid) {
                    $attributes[$dependencyBid] = $dependency;
                    break;
                }
            }
        }

        return $attributes;
    }

    /**
     * Get list of all @see FormulaVariableInterface, which can be used for transmitted @see Attribute independently
     *
     * @param Attribute $target Target entity
     * @return array|FormulaVariableInterface[]
     */
    public function getUsableVariablesFor(Attribute $target): array
    {
        $variables  = $this->variableProvider->variables($target);
        $dependants = $this->getAllDependingOn($target);
        /** @var Attribute $dependant */
        foreach ($dependants as $dependant) {
            if (isset($variables[$dependant->getFormulaVariable()])) {
                unset($variables[$dependant->getFormulaVariable()]);
            }
        }

        return $variables;
    }

    /**
     * Get variables array having test values assigned
     *
     * @param array|FormulaVariableInterface[] Variables to provide test data for
     * @return array|mixed[] Result
     */
    public function getTestVariableValues(array $variables): array
    {
        return $this->variableProvider->getTestVariableValues($variables);
    }

    /**
     * Validate current dependency list, check for circular dependencies (adoption of Kahn's algorithm)
     *
     * @throws CircularDependencyDetectedException When detecting circular dependency
     */
    private function validateDependencies()
    {
        $dependants = $this->fieldDependencies;
        do {
            $changed = self::removeLeafDependenciesFromList($dependants);
        } while ($changed === true);

        if (count($dependants)) {
            throw CircularDependencyDetectedException::create($dependants);
        }
    }

    /**
     * Removes dependencies on leaf nodes from transmitted dependency list
     *
     * @param array $dependants List of dependencies
     * @return bool             Provides true when tree was changed, false if not. If dependencies list is not empty,
     *                          this means that dependency could not be resolved
     */
    private static function removeLeafDependenciesFromList(array &$dependants)
    {
        $change = false;

        // remove dependencies on leaf nodes
        foreach ($dependants as $bid => $dependencies) {
            if (!count($dependencies)) {
                $change = true;
                unset($dependants[$bid]);
            }

            foreach ($dependencies as $key => $dependantOnBid) {
                if ($key !== $dependantOnBid) {
                    throw new \RuntimeException(
                        'Implementation error: Dependants bid must be used for array key as well'
                    );
                }
                if (!isset($dependants[$dependantOnBid])) {
                    unset($dependants[$bid][$key]);
                    $change = true;
                }
            }
        }
        return $change;
    }

}
