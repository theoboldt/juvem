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

use Doctrine\ORM\EntityRepository;


class RecipeRepository extends EntityRepository
{
    /**
     * {@inheritDoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        $qb = $this->createQueryBuilder('recipe');
        $qb->select('recipe', 'ingredients', 'viand', 'properties', 'unit')
           ->leftJoin('recipe.ingredients', 'ingredients')
           ->leftJoin('ingredients.viand', 'viand')
           ->leftJoin('ingredients.unit', 'unit')
           ->leftJoin('viand.properties', 'properties')
           ->andWhere($qb->expr()->eq('recipe.id', ':id'))
           ->setParameter('id', $id);
        $result = $qb->getQuery()->execute();
        
        if (count($result)) {
            $result = reset($result);
            return $result;
        }
        return null;
    }
    
    public function findAll()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(['viand', 'properties'])
           ->from(Viand::class, 'viand')
           ->leftJoin('viand.properties', 'properties');
        $result = $qb->getQuery()->execute();
        
        return parent::findAll();
    }
    
}