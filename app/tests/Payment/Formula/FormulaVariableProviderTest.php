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
use AppBundle\Manager\Payment\PriceSummand\Formula\AttributeChoiceFormulaVariable;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariable;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FormulaVariableProviderTest extends TestCase
{
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

    public function provideValueNotEmptyTypes(): array
    {
        return [
            [TextType::class],
            [TextareaType::class],
            [DateType::class],
            [DateTimeType::class],

        ];
    }

    public function provideBoolean(): array
    {
        return [
            [false],
            [true],
        ];
    }

    public function testNumberTypeVariables(): void
    {
        $field = self::provideAttribute();
        $field->setFieldType(NumberType::class);

        $provider  = new FormulaVariableProvider([$field], []);
        $variables = $provider->variables($field);

        $this->assertArrayHasKey('valueNotEmpty', $variables);
        $variable = $variables['valueNotEmpty'];
        $this->assertInstanceOf(FormulaVariable::class, $variable);
        $this->assertFalse($variable->isNummeric());
        $this->assertTrue($variable->isBoolean());

        $this->assertArrayHasKey('value', $variables);
        $variable = $variables['value'];
        $this->assertInstanceOf(FormulaVariable::class, $variable);
        $this->assertTrue($variable->isNummeric());
        $this->assertFalse($variable->isBoolean());
    }

    /**
     *
     * @dataProvider provideValueNotEmptyTypes
     * @param string $attributeType
     */
    public function testTextFieldVariables(string $attributeType): void
    {
        $field = self::provideAttribute();
        $field->setFieldType($attributeType);

        $provider  = new FormulaVariableProvider([$field], []);
        $variables = $provider->variables($field);

        $this->assertArrayHasKey('valueNotEmpty', $variables);
        $variable = $variables['valueNotEmpty'];
        $this->assertInstanceOf(FormulaVariable::class, $variable);
        $this->assertFalse($variable->isNummeric());
        $this->assertTrue($variable->isBoolean());

        $this->assertCount(1, $variables);
    }

    /**
     *
     * @dataProvider provideBoolean
     * @param bool $multipleChoice
     */
    public function testChoiceType(bool $multipleChoice): void
    {
        $field = self::provideAttribute();
        $field->setFieldType(ChoiceType::class);
        $field->setIsMultipleChoiceType($multipleChoice);

        $option1 = new AttributeChoiceOption();
        $option1->setId(2);
        $field->addChoiceOption($option1);

        $option2 = new AttributeChoiceOption();
        $option2->setId(3);
        $field->addChoiceOption($option2);

        $provider  = new FormulaVariableProvider([$field], []);
        $variables = $provider->variables($field);

        $this->assertArrayHasKey('choice2selected', $variables);
        $this->assertArrayHasKey('choice3selected', $variables);
        $this->assertArrayHasKey('choicesSelectedCount', $variables);

        $this->assertInstanceOf(AttributeChoiceFormulaVariable::class, $variables['choice2selected']);
        $this->assertFalse($variables['choice2selected']->isNummeric());
        $this->assertTrue($variables['choice2selected']->isBoolean());

        $this->assertInstanceOf(AttributeChoiceFormulaVariable::class, $variables['choice3selected']);
        $this->assertFalse($variables['choice3selected']->isNummeric());
        $this->assertTrue($variables['choice3selected']->isBoolean());

        $this->assertInstanceOf(FormulaVariable::class, $variables['choicesSelectedCount']);
        $this->assertTrue($variables['choicesSelectedCount']->isNummeric());
        $this->assertFalse($variables['choicesSelectedCount']->isBoolean());
    }

    public function testTestVariableBooleanResult(): void
    {
        $variable = FormulaVariable::createBoolean('booleanValue1', '');
        $values   = FormulaVariableProvider::getTestVariableValues([$variable]);
        $this->assertEquals(
            [
                'booleanValue1' => true,
            ],
            $values
        );
    }

    public function testTestVariableNumericResult(): void
    {
        $variable = FormulaVariable::createNumeric('numericValue1', '');
        $values   = FormulaVariableProvider::getTestVariableValues([$variable]);
        $this->assertEquals(
            [
                'numericValue1' => 1,
            ],
            $values
        );

    }
    
    public function testEventVariables(): void
    {
        $field = self::provideAttribute();
        $field->setFieldType(TextType::class);

        $variable = new EventSpecificVariable('Test variable');
        $reflectionClass = new \ReflectionClass(EventSpecificVariable::class);
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($variable, 9);

        $event = new Event();
        
        $value = new EventSpecificVariableValue($event, $variable, 3.0);
        $variable->addValue($value);
        
        $provider  = new FormulaVariableProvider([], [$variable]);
        $variables = $provider->variables($field);
        
        $this->assertArrayHasKey('eventSpecific9', $variables);
        $variable = $variables['eventSpecific9'];
        $this->assertInstanceOf(EventSpecificVariable::class, $variable);
        $this->assertTrue($variable->isNummeric());
        $this->assertFalse($variable->isBoolean());
        $resultValue = $variable->getValue($event, false);
        $this->assertInstanceOf(EventSpecificVariable::class, $variable);
        
        $this->assertEquals(3.0, $resultValue->getValue());
    }

}
