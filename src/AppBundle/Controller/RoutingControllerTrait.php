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


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Trait RoutingControllerTrait
 *
 * @see \Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait
 */
trait RoutingControllerTrait
{
    
    /**
     * router
     *
     * @var RouterInterface
     */
    private RouterInterface $router;
    
    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route
     * @param array $parameters
     * @param int $referenceType
     * @return string
     * @see UrlGeneratorInterface
     */
    protected function generateUrl(
        string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }
    
    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param string $url
     * @param int $status
     * @return RedirectResponse
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }
    
    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route
     * @param array $parameters
     * @param int $status
     * @return RedirectResponse
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }
    
    
}