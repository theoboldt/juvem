<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventListeners;

use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Listener responsible to check for secure cache toggle
 */
class ImplicitLoginListener implements EventSubscriberInterface
{
    
    /**
     * Session storage
     *
     * @var Session
     */
    private $session;
    
    public function __construct(Session $session)
    {
        $this->session = $session;
    }
    
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
            SecurityEvents::INTERACTIVE_LOGIN      => 'onSecurityInteractiveLogin',
        ];
    }
    
    /**
     * @param UserEvent $event
     */
    public function onImplicitLogin(UserEvent $event)
    {
        $request = $event->getRequest();
        $this->handle($request);
    }
    
    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user    = $event->getAuthenticationToken()->getUser();
        $request = $event->getRequest();
        if ($user instanceof UserInterface) {
            $this->handle($request);
        }
    }
    
    /**
     * Handle secure cache field transmission
     *
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $secureCache = $request->request->has('_secure_cache') && $request->request->get('_secure_cache') === '1';
        $this->session->set('use_secure_cache', $secureCache);
        
    }
}