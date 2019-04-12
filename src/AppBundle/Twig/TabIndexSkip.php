<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Twig;

use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Twig global for placing installation based app names and legal notices
 *
 * Class Customization
 *
 * @package AppBundle\Twig\Extension
 */
class TabIndexSkip
{
    
    /**
     * User provider
     *
     * @var TokenStorage
     */
    protected $tokenStorage = null;
    
    
    /**
     * Get user if available
     *
     * @return User|null
     */
    private function getUser(): ?User
    {
        if ($this->tokenStorage && $this->tokenStorage->getToken()) {
            return $this->tokenStorage->getToken()->getUser();
        } else {
            return null;
        }
    }
    
    /**
     * Customization constructor
     *
     * @param TokenStorage|null $tokenStorage
     */
    public function __construct(
        TokenStorage $tokenStorage = null
    )
    {
        $this->tokenStorage = $tokenStorage;
    }
    
    /**
     * If user has tabexclude activated, related html is inserted
     *
     * @return string html attribute snippet
     */
    public function skip(): string
    {
        if ($this->getUser() && $this->getUser()->isExcludeHelpTabindex()) {
            return ' tabindex="-1" ';
        } else {
            return '';
        }
    }
    
}
