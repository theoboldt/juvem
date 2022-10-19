<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Encryption\EventUserKeyManagement;

use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Entity\UserRepository;
use AppBundle\Manager\Encryption\EventPublicKeyManager;
use AppBundle\Manager\Encryption\EventUserPublicKeyManager;
use AppBundle\Manager\Encryption\UserPublicKeyManager;
use AppBundle\Security\EventVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class EventUserKeyManager
{

    /**
     * @var AuthorizationChecker
     */
    private AuthorizationChecker $authorizationChecker;

    private AccessDecisionManagerInterface $accessDecisionManager;

    /**
     * @var TokenStorageInterface
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * @var UserRepository 
     */
    private UserRepository $userRepository;

    /**
     * @var EventPublicKeyManager 
     */
    private EventPublicKeyManager $eventPublicKeyManager;

    /**
     * @var UserPublicKeyManager 
     */
    private UserPublicKeyManager $userPublicKeyManager;

    /**
     * @var EventUserPublicKeyManager 
     */
    private EventUserPublicKeyManager $eventUserPublicKeyManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param AuthorizationChecker           $authorizationChecker
     * @param AccessDecisionManagerInterface $accessDecisionManager
     * @param TokenStorageInterface          $tokenStorage
     * @param UserRepository                 $userRepository
     * @param EventPublicKeyManager          $eventPublicKeyManager
     * @param UserPublicKeyManager           $userPublicKeyManager
     * @param EventUserPublicKeyManager      $eventUserPublicKeyManager
     * @param LoggerInterface                $logger
     */
    public function __construct(
        AuthorizationChecker           $authorizationChecker,
        AccessDecisionManagerInterface $accessDecisionManager,
        TokenStorageInterface          $tokenStorage,
        UserRepository                 $userRepository,
        EventPublicKeyManager          $eventPublicKeyManager,
        UserPublicKeyManager           $userPublicKeyManager,
        EventUserPublicKeyManager      $eventUserPublicKeyManager,
        LoggerInterface                $logger
    ) {
        $this->authorizationChecker      = $authorizationChecker;
        $this->accessDecisionManager     = $accessDecisionManager;
        $this->userRepository            = $userRepository;
        $this->eventPublicKeyManager     = $eventPublicKeyManager;
        $this->userPublicKeyManager      = $userPublicKeyManager;
        $this->eventUserPublicKeyManager = $eventUserPublicKeyManager;
        $this->logger                    = $logger;
        $this->tokenStorage              = $tokenStorage;
    }

    /**
     * Decide on behalf of user
     *
     * @param User   $user       User
     * @param array  $attributes An array of attributes associated with the method being invoked
     * @param object $object     The object to secure
     * @return bool              True if the access is granted, false otherwise
     */
    private function decideOnBehalfOfUser(User $user, array $attributes, $object): bool
    {
        $token = new PreAuthenticatedToken($user, null, 'event-user-key-management', $user->getRoles());
        return $this->accessDecisionManager->decide($token, $attributes, $object);
    }

    private function getPasswordForEvent(Event $event): string
    {
        $eventId = $event->getId();
        $user = $this->tokenStorage->getToken();
        if (!$user instanceof User) {
            throw new \RuntimeException('Failed to extract current user');
        }
        $currentUserId = $user->getId();

        if ($this->eventPublicKeyManager->isKeyAvailable($event)) {
            if ($this->eventUserPublicKeyManager->isEncryptedPasswordAvailable($eventId, $currentUserId)) {
                throw new \RuntimeException('Not implemented yet');
            } else {
                throw new EventUserPasswordUnavailableException('Password for event is set but current user does not have access to it');
            }
        } else {
            $this->logger->info('Password for event not set, creating it', ['id' => $eventId]);
            $password = $this->eventPublicKeyManager->createKeyPair($event);
        }

        return $password;
    }

    public function ensureEventUserKeysStored(Event $event, string $eventPassword)
    {
        throw new \RuntimeException('Failed to extract current user');
        if (!$this->authorizationChecker->isGranted(EventVoter::PARTICIPANTS_READ, $event)) {
            //if current user is not assigned to transmitted event, no passwords can be accessed/changed
            return;
        }

        if ($this->eventPublicKeyManager->isKeyAvailable($event)) {

        }

        $user = $this->tokenStorage->getToken();
        if (!$user instanceof User) {
            throw new \RuntimeException('Failed to extract current user');
        }
        $currentUserId = $user->getId();

        //if a user previously assigned to an event is removed,
        // password for all should actually be updated
        $requirePasswordChange = false;

        $timeStart = microtime(true);
        $users     = $this->userRepository->findAll();
        $eventId   = $event->getEid();
        //$this->decideOnBehalfOfUser($user, [EventVoter::PARTICIPANTS_READ], $event)
        foreach ($users as $user) {
            $userId = $user->getUid();
            if ($this->userPublicKeyManager->isKeyAvailable($user)) {
                if ($this->authorizationChecker->isGranted(EventVoter::PARTICIPANTS_READ, $event)) {
                    return;
                    if (!$this->eventUserPublicKeyManager->isEncryptedPasswordAvailable($eventId, $userId)) {
                        $this->eventUserPublicKeyManager->createEventPasswordUserEncrypted(
                            $userId, $eventId, $eventPassword
                        );
                    }
                } else {
                    return;
                    //ensure no event/user encrypted password is available
                }
            }
        }

        $this->logger->notice(
            'Ensured event user exist for event {id} within {duration} ms',
            ['id' => $event->getEid(), 'duration' => (int)round((microtime(true) - $timeStart) * 1000)]
        );
    }

}
