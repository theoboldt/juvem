<?php

namespace AppBundle\Manager\Payment\PriceSummand\Formula;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use Doctrine\ORM\EntityManagerInterface;

class RepositoryFormulaVariableProvider implements FormulaVariableProviderInterface
{

    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * All @see Attribute entities with their related options
     *
     * @var FormulaVariableProvider|null
     */
    private $provider = null;

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
     * Get provider
     *
     * @return FormulaVariableProvider
     */
    private function provider(): FormulaVariableProvider
    {
        if ($this->provider === null) {
            $this->provider = new FormulaVariableProvider(
                $this->em->getRepository(Attribute::class)->findAllWithFormulaAndOptions()
            );
        }
        return $this->provider;
    }

    /**
     * Provide all variables usable for transmitted attribute
     *
     * @param Attribute $attribute
     * @return array|FormulaVariableInterface[] List of variables
     */
    public function variables(Attribute $attribute): array
    {
        return $this->provider()->variables($attribute);
    }
}
