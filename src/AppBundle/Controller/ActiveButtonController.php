<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class ActiveButtonController extends Controller
{
    /**
     * Detail page for one single event
     *
     * @Route("/admin/active/button", name="active_button")
     */
    public function activeButtonHandler(Request $request)
    {
        $token       = $request->get('_token');
        $entityName  = $request->get('entityName');
        $entityId    = $request->get('entityId');
        $property    = $request->get('propertyName');
        $valueNew    = $request->get('value');
        $toggleValue = $request->get('toggle');
        $buttons     = $request->get('buttons');

        switch ($valueNew) {
            case 0:
                $valueNew = false;
                break;
            case 1:
                $valueNew = true;
                break;
            default:
                $valueNew = null;
                break;
        }
        switch ($toggleValue) {
            case 1:
                $toggleValue = true;
                break;
            default:
                $toggleValue = false;
                break;
        }

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken($entityName . $property)) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        try {
            $repository = $this->getDoctrine()
                               ->getRepository('AppBundle:' . $entityName);
        } catch (MappingException $e) {
            throw new AccessDeniedHttpException('Unavailable entity');
        }

        switch ($entityName) {
            case 'Event':
                $idColumn = 'eid';
                break;
            case 'Participation':
                $idColumn = 'pid';
                break;
            default:
                throw new AccessDeniedHttpException('Unmanaged entity');
        }

        /** @var Event $entity */
        $entity = $repository->findOneBy(array($idColumn => $entityId));

        if (!method_exists($entity, $property)) {
            throw new AccessDeniedHttpException('Unavailable property');
        }

        if ($toggleValue) {
            $valueOriginal = $entity->$property();
            $valueNew      = !$valueOriginal;
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


        $html = $this->container->get('templating')->render(
            'common/active-button-content.html.twig', array(
            'buttonIsEnabled' => $valuePerformed,
            'buttons'         => $buttons
        )
        );

        return new JsonResponse(
            array(
                'entityName' => $entityName,
                'entityId' => $entityId,
                'propery' => $property,
                'value' => $valuePerformed,
                'html'  => $html
            )

        );
    }
}
