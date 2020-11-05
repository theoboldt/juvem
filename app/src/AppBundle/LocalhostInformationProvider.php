<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle;


use Symfony\Component\HttpFoundation\RequestStack;

class LocalhostInformationProvider
{

    /**
     * Request stack
     *
     * @var RequestStack
     */
    private $requestStack;


    /**
     * LocalhostInformationProvider constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Determine if this is localhost
     *
     * @return bool
     */
    public function isLocalhost(): bool
    {
        $ip = $this->requestStack->getMasterRequest()->getClientIp();
        return ($ip === '127.0.0.1' || $ip === 'localhost');
    }


}
