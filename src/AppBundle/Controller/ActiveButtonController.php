<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class ActiveButtonController extends Controller
{
    /**
     * Detail page for one single event
     *
     * @Route("/admin/active/button", name="active_button")
     */
    public function activeButtonChangeStateHandler(Request $request)
    {
        $token       = $request->get('_token');
        $entityName  = $request->get('entityName');
        $entityId    = $request->get('entityId');
        $property    = $request->get('propertyName');
        $valueNew    = $request->get('value');
        $toggleValue = $request->get('toggle');
        $buttons     = $request->get('buttons');

        switch ($valueNew) {
            case null:
            default:
                $valueNew = null;
                break;
            case 0:
                $valueNew = false;
                break;
            case 1:
                $valueNew = true;
                break;
        }
        switch ($toggleValue) {
            case null:
            default:
                $toggleValue = null;
                break;
            case 1:
                $toggleValue = true;
                break;
            case 0:
                $toggleValue = false;
                break;
        }

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken($entityName . $property . $entityId)) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        try {
            $repository = $this->getDoctrine()
                               ->getRepository('AppBundle:' . $entityName);
        } catch (MappingException $e) {
            throw new AccessDeniedHttpException('Unavailable entity');
        }

        switch ($entityName) {
            case 'User':
                $idColumn = 'id';
                if (!$this->getUser()->hasRole(User::ROLE_ADMIN_USER)) {
                    throw new AccessDeniedHttpException('Required group not assigned');
                }
                break;
            case 'Event':
                $idColumn = 'eid';
                if (!$this->getUser()->hasRole(User::ROLE_ADMIN_EVENT)) {
                    throw new AccessDeniedHttpException('Required group not assigned');
                }
                break;
            case 'Participation':
                $idColumn = 'pid';
                if (!$this->getUser()->hasRole(User::ROLE_ADMIN_EVENT)) {
                    throw new AccessDeniedHttpException('Required group not assigned');
                }
                break;
            case 'Participant':
                $idColumn = 'aid';
                if (!$this->getUser()->hasRole(User::ROLE_ADMIN_EVENT)) {
                    throw new AccessDeniedHttpException('Required group not assigned');
                }
                break;
            default:
                throw new AccessDeniedHttpException('Unmanaged entity');
        }

        /** @var Event $entity */
        $entity = $repository->findOneBy(array($idColumn => $entityId));

        if (!method_exists($entity, $property)) {
            throw new AccessDeniedHttpException('Unavailable property');
        }
        $valueOriginal = $entity->$property();

        if ($toggleValue !== null) {
            $valueNew = !$valueOriginal;
        }

        if ($valueNew !== null) {
            $propertySetter = 'set' . ucfirst($property);
            $entity->$propertySetter($valueNew);

            $em = $this->getDoctrine()
                       ->getManager();
            $em->persist($entity);
            $em->flush();
        }
        $valuePerformed = $entity->$property();


        $html = $this->container->get('templating')
                                ->render(
                                    'common/active-button-content.html.twig', array(
                                                                                'buttonIsEnabled' => $valuePerformed,
                                                                                'buttons'         => $buttons
                                                                            )
                                );

        return new JsonResponse(
            array(
                'entityName' => $entityName,
                'entityId'   => $entityId,
                'propery'    => $property,
                'value'      => $valuePerformed,
                'html'       => $html
            )

        );
    }
}
