<?php

namespace AppBundle\Entity\AcquisitionAttribute\Formula;

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use AppBundle\Manager\Payment\ExpressionLanguageProvider;
use AppBundle\Manager\Payment\PriceSummand\Formula\CircularDependencyDetectedException;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;


class ValidFormulaValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Lazy initializer @see ExpressionLanguage
     *
     * @var ExpressionLanguageProvider
     */
    protected $expressionLanguageProvider;


    /**
     * ValidFormulaValidator constructor.
     *
     * @param EntityManagerInterface     $em
     * @param ExpressionLanguageProvider $expressionLanguageProvider
     */
    public function __construct(
        EntityManagerInterface $em,
        ExpressionLanguageProvider $expressionLanguageProvider
    ) {
        $this->em                         = $em;
        $this->expressionLanguageProvider = $expressionLanguageProvider;
    }


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
        if (null === $value
            || '' === $value
            || !$value instanceof Attribute
            || !$value->isPriceFormulaEnabled()
            || $value->getPriceFormula() === null
        ) {
            return;
        }
        //even when fetching via doctrine, current formula attribute is already using new formula
        $attributes = $this->em->getRepository(Attribute::class)->findAllWithFormulaAndOptions();
        $eventVariables = $this->em->getRepository(EventSpecificVariable::class)->findAll();
    
        $expressionLanguage = $this->expressionLanguageProvider->provide();
        $resolver           = new FormulaVariableResolver(
            $this->expressionLanguageProvider, $attributes, $eventVariables
        );

        $formula = $value->getPriceFormula();
        try {
            $usableVariables = $resolver->getUsableVariablesFor($value);
            $variables       = $resolver->getTestVariableValues($usableVariables);

            $result = $expressionLanguage->evaluate($formula, $variables);
            if (!is_numeric($result)) {
                $this->buildViolation($formula, $constraint, 'Das Ergebnis der Formel ist keine Zahl');
            }
            try {
                $expressionLanguage->evaluate($formula, $variables);
            } catch (\Exception $e) {
                //Please note that there are more potential causes of divisions by zero, eg by using the formula
                // 10/(value-1) when value = 1. This can not be prevented right now
                $message = $e->getMessage();
                if ($message === 'Warning: Division by zero') {
                    $this->buildViolation(
                        $formula, $constraint, 'Abhängig von value kann die Formel Divison durch null auslösen'
                    );
                } else {
                    throw $e;
                }
            }

        } catch (SyntaxError $e) {
            $this->buildViolation($formula, $constraint, $e->getMessage());
        } catch (CircularDependencyDetectedException $e) {
            $this->buildViolation($value, $constraint, $e->getMessage());
            return;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($message === 'Warning: Division by zero') {
                $message = 'Formel enthält Division durch null';
            }
            $this->buildViolation($formula, $constraint, $message);
        }
    }

    /**
     * Build violation
     *
     * @param string|mixed $value
     * @param Constraint   $constraint
     * @param string       $message
     */
    private function buildViolation($value, Constraint $constraint, string $message)
    {
        $this->context->buildViolation($constraint->message)
                      ->setParameter('{{ formula }}', $value)
                      ->setParameter('{{ error }}', $message)
                      ->atPath('priceFormula')
                      ->addViolation();
    }
}
