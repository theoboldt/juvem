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

use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OnlyNumericManagementDescriptionsUsedValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof OnlyNumericManagementDescriptionsUsed) {
            throw new UnexpectedTypeException($constraint, OnlyNumericManagementDescriptionsUsed::class);
        }
        
        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value
            || '' === $value
            || !$value instanceof AttributeChoiceOption
            || !$value->getAttribute()
            || !$value->getAttribute()->isPriceFormulaEnabled()
            || $value->getAttribute()->getPriceFormula() === null
            || !empty($value->getPriceFormula())
            || strpos($value->getAttribute()->getPriceFormula(), 'value') === false
        ) {
            return;
        }
        if (!is_numeric(str_replace(',', '.', $value->getManagementTitle(true)))) {
            $this->context->buildViolation($constraint->message)
                          ->atPath('managementTitle')
                          ->addViolation();
        }
    }
}