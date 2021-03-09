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

use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\InvalidTokenHttpException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;


class ActiveButtonController
{
    
    use DoctrineAwareControllerTrait, AuthorizationAwareControllerTrait, RenderingControllerTrait;
    
    const USER_ID_SELF = '__self__';
    
    /**
     * security.csrf.token_manager
     *
     * @var CsrfTokenManagerInterface
     */
    private CsrfTokenManagerInterface $csrfTokenManager;
    
    /**
     * ActiveButtonController constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param Environment $twig
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param CsrfTokenManagerInterface $csrfTokenManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        Environment $twig,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        CsrfTokenManagerInterface $csrfTokenManager
    )
    {
        $this->csrfTokenManager     = $csrfTokenManager;
        $this->doctrine             = $doctrine;
        $this->twig                 = $twig;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
    }
    
    /**
     * Detail page for one single event
     *
     * @CloseSessionEarly
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
        $isXs        = $request->get('isXs');

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
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken($entityName . $property . $entityId)) {
            throw new InvalidTokenHttpException();
        }

        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:' . $entityName);

        switch ($entityName) {
            case 'User':
                $idColumn = 'id';
                if ($entityId === self::USER_ID_SELF && in_array($property, ['isExcludeHelpTabindex'])) {
                    //edit own user is always allowed for transmitted properties
                    $entityId = $this->getUser()->getId();
                } elseif (!$this->getUser()->hasRole(User::ROLE_ADMIN_USER)) {
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
                throw new \InvalidArgumentException('Unmanaged entity');
        }

        $entity = $repository->findOneBy(array($idColumn => $entityId));

        /** @var Event $entity */
        if ($entity instanceof Event) {
            $this->denyAccessUnlessGranted('edit', $entity);
        }

        if (!method_exists($entity, $property)) {
            throw new \InvalidArgumentException('Unavailable property');
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
    
        $html = $this->twig->render(
            'common/active-button-content.html.twig',
            [
                'isXs'            => $isXs,
                'buttonIsEnabled' => $valuePerformed,
                'buttons'         => $buttons
            ]
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
