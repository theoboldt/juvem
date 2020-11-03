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


use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Trait RoutingControllerTrait
 *
 * @see \Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait
 */
trait AuthorizationAwareControllerTrait
{
    /**
     * security.authorization_checker
     *
     * @var AuthorizationCheckerInterface|null
     */
    private ?AuthorizationCheckerInterface $authorizationChecker;
    
    /**
     * security.token_storage
     *
     * @var TokenStorageInterface|null
     */
    private ?TokenStorageInterface $tokenStorage;
    
    /**
     * Get a user from the Security Token Storage.
     *
     * @return UserInterface|object|null
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser()
    {
        if (!$this->tokenStorage) {
            throw new \LogicException('Token storage is not configured in this service');
        }
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }
        
        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }
        
        return $user;
    }
    
    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied subject.
     *
     * @param $attributes
     * @param null $subject
     * @return bool
     */
    protected function isGranted($attributes, $subject = null): bool
    {
        if (!$this->authorizationChecker) {
            throw new \LogicException('Authorization Checker is not configured in this service');
        }
        return $this->authorizationChecker->isGranted($attributes, $subject);
    }
    
    /**
     * Throws an exception unless the attributes are granted against the current authentication token and optionally
     * supplied subject.
     *
     * @param $attributes
     * @param null $subject
     * @param string $message
     */
    protected function denyAccessUnlessGranted($attributes, $subject = null, string $message = 'Access Denied.')
    {
        if (!$this->isGranted($attributes, $subject)) {
            $exception = $this->createAccessDeniedException($message);
            $exception->setAttributes($attributes);
            $exception->setSubject($subject);
            
            throw $exception;
        }
    }
    
    
    /**
     * Returns an AccessDeniedException.
     *
     * This will result in a 403 response code. Usage example:
     *
     *     throw $this->createAccessDeniedException('Unable to access this page!');
     *
     * @param string $message
     * @param \Throwable|null $previous
     * @return AccessDeniedException
     */
    protected function createAccessDeniedException(string $message = 'Access Denied.', \Throwable $previous = null
    ): AccessDeniedException
    {
        return new AccessDeniedException($message, $previous);
    }
    
}