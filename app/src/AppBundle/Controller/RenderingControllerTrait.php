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


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;

/**
 * Trait RenderingControllerTrait
 *
 * @see \Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait
 */
trait RenderingControllerTrait
{
    
    /**
     * twig
     *
     * @var Environment
     */
    private Environment $twig;
    
    /**
     * Returns a rendered view.
     *
     * @param string $view
     * @param array $parameters
     * @return string
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->twig->render($view, $parameters);
    }
    
    /**
     * Renders a view.
     *
     * @param string $view
     * @param array $parameters
     * @param Response|null $response
     * @return Response
     */
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $content = $this->twig->render($view, $parameters);
        
        if (null === $response) {
            $response = new Response();
        }
        
        $response->setContent($content);
        
        return $response;
    }
    
    /**
     * Streams a view.
     *
     * @param string $view
     * @param array $parameters
     * @param StreamedResponse|null $response
     * @return StreamedResponse
     */
    protected function stream(string $view, array $parameters = [], StreamedResponse $response = null): StreamedResponse
    {
        $callback = function () use ($view, $parameters) {
            $this->twig->display($view, $parameters);
        };
        
        if (null === $response) {
            return new StreamedResponse($callback);
        }
        
        $response->setCallback($callback);
        
        return $response;
    }
    
}