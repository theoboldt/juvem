<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\Meals;


use Doctrine\ORM\EntityManagerInterface;

class FoodService
{
    
    /**
     * EntityManager
     *
     * @var EntityManagerInterface
     */
    protected $em;
    
    /**
     * Cache for food properties
     *
     * @var null|array|FoodProperty[]
     */
    private $foodProperties;
    
    /**
     * FoodService constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /**
     * Get all {@see FoodProperty}
     *
     * @return array|FoodProperty[]
     */
    public function findAllFoodProperties(): array
    {
        if ($this->foodProperties === null) {
            $this->foodProperties = $this->em->getRepository(FoodProperty::class)->findAll();
        }
        return $this->foodProperties;
    }
    
    /**
     * Get list of all viands including their assigned properties
     *
     * @return array|Viand[]
     */
    public function findAllViands(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(['viand', 'properties'])
           ->from(Viand::class, 'viand')
           ->leftJoin('viand.properties', 'properties');
        return $qb->getQuery()->execute();
    }
    
    /**
     * Get all {@see FoodProperty} which are not assigned to transmitted entity
     *
     * @param HasFoodPropertiesAssignedInterface $entity Related Entity
     * @return array|FoodProperty[]
     */
    public function findAllFoodPropertiesNotAssigned(HasFoodPropertiesAssignedInterface $entity): array
    {
        $notAssigned = $this->findAllFoodProperties();
        foreach ($entity->getProperties() as $property) {
            $propertyId = $property->getId();
            foreach ($notAssigned as $key => $notAssignedProperty) {
                if ($propertyId === $notAssignedProperty->getId()) {
                    unset($notAssigned[$key]);
                    break;
                }
            }
        }
        
        return $notAssigned;
    }
    
    
    /**
     * Get all accumulated ingredients
     *
     * @param Recipe $recipe
     * @return array|AccumulatedIngredient[]
     */
    public function accumulatedIngredients(Recipe $recipe): array
    {
        $accumulated = [];
        foreach ($recipe->getIngredients() as $ingredient) {
            $viandId = $ingredient->getViand()->getId();
            if (!isset($accumulated[$viandId])) {
                $accumulated[$viandId] = AccumulatedIngredient::createForRecipeIngredient($ingredient);
            } else {
                $accumulated[$viandId]->addRecipeIngredient($ingredient);
            }
        }
        return $accumulated;
    }
    
    
}