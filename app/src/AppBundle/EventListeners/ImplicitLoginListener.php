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

use AppBundle\Manager\UserPublicKeyManager;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use Psr\Log\LoggerInterface;
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
    private Session $session;

    /**
     * @var UserPublicKeyManager
     */
    private UserPublicKeyManager $userPublicKeyManager;

    /**
     * @var LoggerInterface 
     */
    private LoggerInterface $logger;

    /**
     * @param Session              $session
     * @param UserPublicKeyManager $userPublicKeyManager
     * @param LoggerInterface      $logger
     */
    public function __construct(Session $session, UserPublicKeyManager $userPublicKeyManager, LoggerInterface $logger)
    {
        $this->session              = $session;
        $this->userPublicKeyManager = $userPublicKeyManager;
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
        $this->handleSecureCacheKey($request);
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user    = $event->getAuthenticationToken()->getUser();
        $request = $event->getRequest();
        if ($user instanceof UserInterface) {
            $this->handleSecureCacheKey($request);
            $this->handleUserKeys($user, $request);
        }
    }

    /**
     * Handle secure cache field transmission
     *
     * @param Request $request
     */
    private function handleSecureCacheKey(Request $request)
    {
        $secureCache = $request->request->has('_secure_cache') && $request->request->get('_secure_cache') === '1';
        $this->session->set('use_secure_cache', $secureCache);
    }

    /**
     * Ensure key pair is available for user
     *
     * @param UserInterface $user
     * @param Request       $request
     */
    private function handleUserKeys(UserInterface $user, Request $request): void
    {
        if (!UserPublicKeyManager::isConfigured()) {
            $this->logger->info(UserPublicKeyManager::class . ' is not configured');
            return;
        }
        
        $password = $request->request->get('_password', null);
        if ($password) {
            $this->userPublicKeyManager->ensureKeyPairAvailable($user, $password);
        }
    }
}
