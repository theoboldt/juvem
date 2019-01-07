<?php

namespace AppBundle\Entity\AcquisitionAttribute\Formula;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;


class ValidFormulaValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidFormula) {
            throw new UnexpectedTypeException($constraint, ValidFormula::class);
        }
        
        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }
        
        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }
        
        $expressionLanguage = new ExpressionLanguage();
        try {
            $result = $expressionLanguage->evaluate(
                $value,
                [
                    'value' => 10,
                ]
            );
            if (!is_numeric($result)) {
                $this->buildViolation($value, $constraint, 'Das Ergebnis der Formel ist keine Zahl');
            }
            try {
                $expressionLanguage->evaluate(
                    $value,
                    [
                        'value' => 0,
                    ]
                );
            } catch (\Exception $e) {
                //Please note that there are more potential causes of divisions by zero, eg by using the formula
                // 10/(value-1) when value = 1. This can not be prevented right now
                $message = $e->getMessage();
                if ($message === 'Warning: Division by zero') {
                    $this->buildViolation(
                        $value, $constraint, 'Abhängig von value kann die Formel Divison durch null auslösen'
                    );
                } else {
                    throw $e;
                }
            }
            
        } catch (SyntaxError $e) {
            $this->buildViolation($value, $constraint, $e->getMessage());
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($message === 'Warning: Division by zero') {
                $message = 'Formel enthält Division durch null';
            }
            $this->buildViolation($value, $constraint, $message);
        }
    }
    
    /**
     * Build violation
     *
     * @param string|mixed $value
     * @param Constraint $constraint
     * @param string $message
     */
    private function buildViolation($value, Constraint $constraint, string $message)
    {
        $this->context->buildViolation($constraint->message)
                      ->setParameter('{{ formula }}', $value)
                      ->setParameter('{{ error }}', $message)
                      ->addViolation();
    }
}