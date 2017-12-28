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

use AppBundle\EventNotFoundHttpException;
use AppBundle\InvalidTokenHttpException;
use AppBundle\JsonResponse;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ExceptionController extends \Symfony\Bundle\TwigBundle\Controller\ExceptionController
{

    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $accept        = $request->headers->get('accept', '');
        $requestIsJson = strpos($accept, 'application/json') !== false || strpos($accept, 'text/javascript') !== false;

        switch ($exception->getClass()) {
            case EventNotFoundHttpException::class:
                return new Response($this->twig->render('event/public/miss.html.twig'), Response::HTTP_NOT_FOUND);
                break;
            case InvalidTokenHttpException::class:
                if ($requestIsJson) {
                    return JsonResponse::createError(
                        'In der Zwischenzeit haben sich Teile dieser Seite geändert. Sie müssen sie aktualisieren, um fortfahren zu können.'
                    );
                } else {
                    return new Response(
                        $this->twig->render('event/public/token-outdated.html.twig'), Response::HTTP_BAD_REQUEST
                    );
                }
                break;
            case AccessDeniedHttpException::class:
                if ($requestIsJson) {
                    return JsonResponse::createError(
                        'Sie haben nicht die nötigen Berechtigung um die gewünschte Aktion durchzuführen.'
                    )->setStatusCode(Response::HTTP_FORBIDDEN);
                } else {
                    return new Response(
                        $this->twig->render('event/public/unallowed.html.twig'), Response::HTTP_FORBIDDEN
                    );
                }
                break;
            default:
                return parent::showAction($request, $exception, $logger);
                break;
        }
    }
}