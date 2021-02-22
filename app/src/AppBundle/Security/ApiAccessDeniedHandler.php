<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Security;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    
    /**
     * @param Request $request
     * @param AccessDeniedException $accessDeniedException
     * @return JsonResponse
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        return new JsonResponse(
            ['success' => false, 'message' => 'Access denied.'],
            Response::HTTP_FORBIDDEN
        );
    }
}