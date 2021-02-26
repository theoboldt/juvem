<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Controller\Event;


use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventFileShare;
use AppBundle\Entity\EventUserAssignment;
use AppBundle\Entity\User;
use AppBundle\Form\EventAddUserAssignmentsType;
use AppBundle\Form\EventAddUserCloudAssignmentsType;
use AppBundle\Form\EventUserAssignmentsType;
use AppBundle\Form\EventUserCloudAssignmentsType;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\InvalidTokenHttpException;
use AppBundle\JsonResponse;
use AppBundle\Manager\Filesharing\EventFileSharingManager;
use AppBundle\Manager\Filesharing\NextcloudFile;
use AppBundle\Manager\Filesharing\NextcloudFileInterface;
use AppBundle\Security\EventVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

/**
 * AdminCloudController
 *
 * @Security("is_granted('ROLE_ADMIN')")
 */
class AdminCloudController
{
    
    use DoctrineAwareControllerTrait, AuthorizationAwareControllerTrait, RenderingControllerTrait, FormAwareControllerTrait, RoutingControllerTrait;
    
    
    /**
     * security.csrf.token_manager
     *
     * @var CsrfTokenManagerInterface
     */
    private CsrfTokenManagerInterface $csrfTokenManager;
    
    /**
     * @var EventFileSharingManager
     */
    private EventFileSharingManager $fileSharingManager;
    
    
    /**
     * AdminCloudController constructor.
     *
     * @param EventFileSharingManager $fileSharingManager
     * @param Environment $twig
     * @param FormFactoryInterface $formFactory
     * @param RouterInterface $router
     * @param ManagerRegistry $doctrine
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EventFileSharingManager $fileSharingManager,
        Environment $twig,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        ManagerRegistry $doctrine,
        CsrfTokenManagerInterface $csrfTokenManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->fileSharingManager   = $fileSharingManager;
        $this->csrfTokenManager     = $csrfTokenManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
        $this->twig                 = $twig;
        $this->formFactory          = $formFactory;
        $this->router               = $router;
        $this->doctrine             = $doctrine;
    }
    
    /**
     * Provide all shares for event
     *
     * @param Event $event
     * @return EventFileShare[]
     */
    private function findSharesForEvent(Event $event): array
    {
        $repository = $this->getDoctrine()->getRepository(EventFileShare::class);
        return $repository->findForEvent($event);
    }
    
    /**
     * Ensure folders and groups for this user are existing in cloud
     *
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/cloud/{token}/enable", requirements={"eid": "\d+", "token": ".*"},
     *                                                   name="admin_event_cloud_enable")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     * @param Event $event
     * @param string $token
     * @return Response
     */
    public function enableCloudAction(Event $event, string $token): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('cloud-' . $event->getEid(), $token))) {
            throw new InvalidTokenHttpException();
        }
        
        $this->fileSharingManager->ensureEventCloudSharesAvailable($event);
        
        return new JsonResponse([]);
    }
    
    /**
     * Remove folders and groups related to this event
     *
     * @Route("/admin/event/{eid}/cloud/{token}/disable", requirements={"eid": "\d+", "token": ".*"},
     *                                                   name="admin_event_cloud_disable")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     * @param Event $event
     * @param string $token
     * @return Response
     */
    public function disableCloudAction(Event $event, string $token): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('cloud-' . $event->getEid(), $token))) {
            throw new InvalidTokenHttpException();
        }
        
        $this->fileSharingManager->removeEventCloudShares($event);
        
        return new JsonResponse([]);
    }
    
    /**
     * Ensure all users having this event assigned have access to the related share
     *
     * @Route("/admin/event/{eid}/cloud/{token}/share", requirements={"eid": "\d+", "token": ".*"},
     *                                                  name="admin_event_share_update")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     * @param Event $event
     * @param string $token
     * @return Response
     */
    public function updateCloudShareAction(Event $event, string $token): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('cloud-share-' . $event->getEid(), $token))) {
            throw new InvalidTokenHttpException();
        }
        
        $this->fileSharingManager->updateCloudShareAssignments($event);
        
        return new JsonResponse([]);
    }
    
    /**
     * Ensure all users having this event assigned have access to the related share
     *
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/cloud/files.json", requirements={"eid": "\d+"})
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('cloud_access_team', event) or is_granted('cloud_access_management', event)")
     * @param Event $event
     * @param string $token
     * @return Response
     */
    public function listCloudFilesAction(Event $event): Response
    {
        $files = [];
        if ($this->isGranted(EventVoter::CLOUD_ACCESS_TEAM, $event)) {
            $files = array_merge($files, $this->fileSharingManager->listFiles($event, EventFileShare::PURPOSE_TEAM));
        }
        if ($this->isGranted(EventVoter::CLOUD_ACCESS_MANAGEMENT, $event)) {
            $files = array_merge(
                $files, $this->fileSharingManager->listFiles($event, EventFileShare::PURPOSE_MANAGEMENT)
            );
        }
        
        $result = [];
        /** @var NextcloudFileInterface $file */
        foreach ($files as $file) {
            $fileData = [
                'filename'      => $file->getName(),
                'filesize'      => $file->getSize(),
                'last_modified' => $file->getLastModified()->format(Event::DATE_FORMAT_DATE_TIME),
            ];
            if ($file instanceof NextcloudFile) {
                $fileData['content_type'] = $file->getContentType();
            }
            
            $result[] = $fileData;
        }
        
        return new JsonResponse(['files' => $result]);
    }
    
    /**
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/cloud", requirements={"eid": "\d+"}, name="admin_event_cloud")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     * @param Event $event
     * @return Response
     */
    public function manageEventCloudConfiguration(Event $event, Request $request): Response
    {
        $originalAssignments = new ArrayCollection();
        $em                  = $this->getDoctrine()->getManager();
        
        $formAddUsers = $this->createForm(EventAddUserCloudAssignmentsType::class, null, ['event' => $event]);
        $formAddUsers->handleRequest($request);
        if ($formAddUsers->isSubmitted() && $formAddUsers->isValid()) {
            $assignUser = $formAddUsers->get('assignUser');
            /** @var User $user */
            foreach ($assignUser->getData() as $user) {
                $assignment = new EventUserAssignment($event, $user);
                $event->getUserAssignments()->add($assignment);
            }
            $em->persist($event);
            $em->flush();
            
            return $this->redirectToRoute('admin_event_cloud', ['eid' => $event->getEid()]);
        }
        foreach ($event->getUserAssignments() as $assignment) {
            $originalAssignments->add($assignment);
        }
        foreach ($event->getUserAssignments() as $assignment) {
            $originalAssignments->add($assignment);
        }
        
        $form = $this->createForm(EventUserCloudAssignmentsType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($originalAssignments as $assignment) {
                if (false === $event->getUserAssignments()->contains($assignment)) {
                    $em->remove($assignment);
                }
            }
            $em->persist($event);
            $em->flush();
            return $this->redirectToRoute('admin_event_cloud', ['eid' => $event->getEid()]);
        }
        
        return $this->render(
            'event/admin/cloud-detail.html.twig',
            [
                'event'       => $event,
                'form'        => $form->createView(),
                'formAddUser' => $formAddUsers->createView(),
            ]
        );
        
    }
}
