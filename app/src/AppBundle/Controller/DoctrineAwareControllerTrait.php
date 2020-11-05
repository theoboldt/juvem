<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;


use Doctrine\Persistence\ManagerRegistry;

/**
 * Trait RoutingControllerTrait
 *
 * @see \Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait
 */
trait DoctrineAwareControllerTrait
{
    /**
     * doctrine
     *
     * @var ManagerRegistry|null
     */
    private ?ManagerRegistry $doctrine;
    
    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return ManagerRegistry
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    protected function getDoctrine()
    {
        if (!$this->doctrine) {
            throw new \LogicException('Doctrine is not configured in this service');
        }
        
        return $this->doctrine;
    }
    
    
}