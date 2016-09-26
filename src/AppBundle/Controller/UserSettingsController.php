<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserSettingsController extends Controller
{
    /**
     * @Route("/user/settings/load", name="user_settings_load")
     */
    public function userSettingsLoadAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user) {
            $settings = array('settings' => $user->getSettings(true), 'hash' => $user->getSettingsHash());
        } else {
            $settings = array();
        }
        return new JsonResponse($settings);
    }

    /**
     * @Route("/user/settings/store", name="user_settings_store")
     */
    public function userSettingsStoreAction(Request $request)
    {
        $token = $request->get('_token');
        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('user-settings')) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setSettings($request->get('settings'));

        if ($user) {
            $em = $this->getDoctrine()
                       ->getManager();
            $em->persist($user);
            $em->flush();
        }
        return new Response();
    }
}
