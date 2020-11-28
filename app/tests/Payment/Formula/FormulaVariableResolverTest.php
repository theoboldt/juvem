<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Payment\Formula;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariableValue;
use AppBundle\Entity\Event;
use AppBundle\Manager\Payment\ExpressionLanguageProvider;
use AppBundle\Manager\Payment\PriceSummand\Formula\AttributeFormulaVariable;
use AppBundle\Manager\Payment\PriceSummand\Formula\CircularDependencyDetectedException;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FormulaVariableResolverTest extends TestCase
{

    static ?Attribute $attribute2 = null;

    static ?Attribute $attribute3 = null;

    static ?EventSpecificVariable $eventVariable = null;

    /**
     * @param int $id
     * @return Attribute
     */
    private static function provideAttribute(int $id = 1): Attribute
    {
        $attribute       = new Attribute();
        $reflectionClass = new \ReflectionClass(Attribute::class);

        $reflectionProperty = $reflectionClass->getProperty('bid');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($attribute, $id);

        return $attribute;
    }

    private static function provideResolver(): FormulaVariableResolver
    {
        $tmpDir                     = __DIR__ . '/../../../../var/tmp/test_expression_language';
        $expressionLanguageProvider = new ExpressionLanguageProvider($tmpDir);

        if (!self::$attribute2) {
            self::$attribute2 = self::provideAttribute(2);
            self::$attribute2->setFieldType(ChoiceType::class);
            self::$attribute2->setIsMultipleChoiceType(false);

            $option1 = new AttributeChoiceOption();
            $option1->setId(5);
            self::$attribute2->addChoiceOption($option1);

            $option2 = new AttributeChoiceOption();
            $option2->setId(6);
            self::$attribute2->addChoiceOption($option2);
        }

        if (!self::$attribute3) {
            self::$attribute3 = self::provideAttribute(3);
            self::$attribute3->setFieldType(TextType::class);
        }

        if (!self::$eventVariable) {
            self::$eventVariable = new EventSpecificVariable('Test variable');
            $reflectionClass     = new \ReflectionClass(EventSpecificVariable::class);
            $reflectionProperty  = $reflectionClass->getProperty('id');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue(self::$eventVariable, 9);

            $event = new Event();

            $value = new EventSpecificVariableValue($event, self::$eventVariable, 3.0);
            self::$eventVariable->addValue($value);
        }

        $attributes     = [2 => self::$attribute2, 3 => self::$attribute3];
        $eventVariables = [self::$eventVariable];

        $resolver = new FormulaVariableResolver(
            $expressionLanguageProvider,
            $attributes,
            $eventVariables
        );

        return $resolver;
    }

    public function testVariablesForChoice(): void
    {
        $resolver  = self::provideResolver();
        $variables = $resolver->getUsableVariablesFor(self::$attribute2);

        $this->assertArrayHasKey('eventSpecific9', $variables);
        $this->assertArrayHasKey('choice5selected', $variables);
        $this->assertArrayHasKey('choice6selected', $variables);
        $this->assertArrayHasKey('choicesSelectedCount', $variables);
        $this->assertArrayHasKey('field3', $variables);
        $this->assertCount(5, $variables);
    }

    public function testVariablesForText(): void
    {
        $resolver  = self::provideResolver();
        $variables = $resolver->getUsableVariablesFor(self::$attribute3);

        $this->assertArrayHasKey('eventSpecific9', $variables);
        $this->assertArrayHasKey('field2', $variables);
        $variable = $variables['field2'];
        $this->assertInstanceOf(AttributeFormulaVariable::class, $variable);
        $this->assertEquals('field2', $variable->getName());
        $this->assertTrue($variable->isNummeric());
        $this->assertFalse($variable->isBoolean());
        $this->assertArrayHasKey('valueNotEmpty', $variables);
    }

    public function testNoDependencies(): void
    {
        $resolver     = self::provideResolver();
        $dependencies = $resolver->getDependenciesFor(self::$attribute2);
        $this->assertEquals([], $dependencies);

        $depending = $resolver->getAllDependingOn(self::$attribute3);
        $this->assertEquals([], $depending);

        $dependencies = $resolver->getDependenciesFor(self::$attribute3);
        $this->assertEquals([], $dependencies);

        $depending = $resolver->getAllDependingOn(self::$attribute2);
        $this->assertEquals([], $depending);
    }

    public function testNoUsedVariables(): void
    {
        $resolver = self::provideResolver();
        $used     = $resolver->getUsedVariables(self::$attribute2);
        $this->assertEquals([], $used);

        $used = $resolver->getUsedVariables(self::$attribute3);
        $this->assertEquals([], $used);
    }

    public function testDependencyOnOtherAttribute(): void
    {
        $resolver = self::provideResolver();

        self::$attribute2->setPriceFormula(
            'field3 * 2'
        );

        $dependencies = $resolver->getDependenciesFor(self::$attribute2);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(self::$attribute3, $dependencies[0]);

        $depending = $resolver->getAllDependingOn(self::$attribute3);
        $this->markTestIncomplete('Result of getAllDependingOn is suspicious, needs to be checked');
        /*
        TODO add test
        $this->assertCount(1, $depending);
        $dependingEntry = reset($depending);
        $this->assertEquals(self::$attribute2, $dependingEntry);
        */
    }

    public function testDetectCircularDependency(): void
    {
        $resolver = self::provideResolver();

        self::$attribute3->setPriceFormula(
            'field2 * 2'
        );
        self::$attribute2->setPriceFormula(
            'field3 * 2'
        );

        $this->expectException(CircularDependencyDetectedException::class);
        $this->expectExceptionMessage('Circular dependency: 2 depends on 3 while 3 depends on 2');

        $resolver->getDependenciesFor(self::$attribute2);
    }
}
