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


use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Trait FlashBagAwareControllerTrait
 *
 * @see \Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait
 */
trait FlashBagAwareControllerTrait
{
    /**
     * session
     *
     * @var SessionInterface|null
     */
    private ?SessionInterface $session;
    
    /**
     * Adds a flash message to the current session for type.
     *
     * @throws \LogicException
     *
     * @final
     */
    protected function addFlash(string $type, $message)
    {
        if (!$this->session) {
            throw new \LogicException('Session is not configured in this service');
        }
        
        $this->session->getFlashBag()->add($type, $message);
    }
    
}