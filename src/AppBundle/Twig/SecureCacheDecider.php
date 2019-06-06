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


use Symfony\Component\HttpFoundation\Session\Session;

class SecureCacheDecider
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
     * Check if enabled
     *
     * @return bool
     */
    public function enabled()
    {
        return $this->session !== null && $this->session->get('use_secure_cache', false);
    }
    
}