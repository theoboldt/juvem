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

use AppBundle\EventNotFoundHttpException;
use AppBundle\InvalidTokenHttpException;
use AppBundle\JsonResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * @var Environment
     */
    private Environment $twig;
    
    /**
     * @param Environment $twig
     * @param null|LoggerInterface $logger
     */
    public function __construct(Environment $twig, ?LoggerInterface $logger = null)
    {
        $this->twig   = $twig;
        $this->logger = $logger ?: new NullLogger();
    }
    
    /**
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $request       = $event->getRequest();
        $accept        = $request->headers->get('accept', '');
        $requestIsJson = strpos($accept, 'application/json') !== false
                         || strpos($accept, 'text/javascript') !== false;
        
        $exception = $event->getThrowable();
        $response  = null;
        $doLog     = true;
        if ($exception instanceof EventNotFoundHttpException) {
            $doLog    = false;
            $response = new Response($this->twig->render('event/public/miss.html.twig'), Response::HTTP_NOT_FOUND);
        } elseif ($exception instanceof InvalidTokenHttpException) {
            $doLog = false;
            if ($requestIsJson) {
                $response = JsonResponse::createError(
                    'In der Zwischenzeit haben sich Teile dieser Seite geändert. Sie müssen sie aktualisieren, um fortfahren zu können.'
                );
            } else {
                $response = new Response(
                    $this->twig->render('event/public/token-outdated.html.twig'), Response::HTTP_BAD_REQUEST
                );
            }
        } elseif ($exception instanceof AccessDeniedHttpException) {
            if ($requestIsJson) {
                $response = JsonResponse::createError(
                    'Sie haben nicht die nötigen Berechtigung um die gewünschte Aktion durchzuführen.'
                )->setStatusCode(Response::HTTP_FORBIDDEN);
            } else {
                $response = new Response(
                    $this->twig->render('event/public/unallowed.html.twig'), Response::HTTP_FORBIDDEN
                );
            }
        }
        if ($response) {
            $event->setResponse($response);
        }
        
        if ($doLog) {
            $this->logger->error(
                'Exception {message} in {file} at {line}',
                [
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine()
                ]
            );
            if ($exception instanceof \Exception) {
                $this->logger->error('Stack trace');
                $flattenException = FlattenException::create($exception);
                foreach ($flattenException->getTrace() as $trace) {
                    $traceMessage = sprintf('  at %s line %s', $trace['file'], $trace['line']);
                    $this->logger->error($traceMessage);
                }
            } elseif ($exception instanceof \Throwable) {
                $this->logger->error('Stack trace ' . $exception->getTraceAsString());
            }
        }
        
    }
}