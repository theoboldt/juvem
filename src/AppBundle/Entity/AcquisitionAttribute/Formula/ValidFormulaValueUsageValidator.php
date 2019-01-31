<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\AcquisitionAttribute\Formula;

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as FormChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType as FormNumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as FormTextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType as FormDateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as FormDateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType as FormTextType;


class ValidFormulaValueUsageValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidFormulaValueUsage) {
            throw new UnexpectedTypeException($constraint, ValidFormulaValueUsage::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value
            || '' === $value
            || !$value instanceof Attribute
            || !$value->isPriceFormulaEnabled()
            || $value->getPriceFormula() === null
        ) {
            return;
        }

        $formula       = $value->getPriceFormula();
        $containsValue = $value->getPriceFormula() !== null && strpos($formula, 'value') !== false;

        switch ($value->getFieldType()) {
            case FormTextType::class;
            case FormTextareaType::class;
            case FormDateType::class;
            case FormDateTimeType::class;
            case FormNumberType::class;
                //intentionally left empty
                break;
            case FormChoiceType::class;
                if ($value->isMultipleChoiceType() && $containsValue) {
                    $this->buildViolation(
                        $formula, $constraint, $value->getFieldType(true) . ' (Mehrere Optionen auswählbar)'
                    );
                }
                break;
            default:
                $allowValue = false;
                $this->buildViolation($formula, $constraint, $value->getFieldType(true));
                break;
        }

    }

    /**
     * Build violation
     *
     * @param string|mixed $formula
     * @param Constraint $constraint
     * @param string $type
     */
    private function buildViolation($formula, Constraint $constraint, string $type)
    {
        $this->context->buildViolation($constraint->message)
                      ->atPath('priceFormula')
                      ->setParameter('{{ formula }}', $formula)
                      ->setParameter('{{ type }}', $type)
                      ->addViolation();
    }
}
