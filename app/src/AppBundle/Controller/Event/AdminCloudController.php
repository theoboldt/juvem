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
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventFileShare;
use AppBundle\InvalidTokenHttpException;
use AppBundle\JsonResponse;
use AppBundle\Manager\Filesharing\EventFileSharingManager;
use AppBundle\Manager\Filesharing\NextcloudFile;
use AppBundle\Manager\Filesharing\NextcloudFileInterface;
use AppBundle\Security\EventVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
    
    use DoctrineAwareControllerTrait, AuthorizationAwareControllerTrait, RenderingControllerTrait;

    
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
     * @param EventFileSharingManager       $fileSharingManager
     * @param Environment                   $twig
     * @param CsrfTokenManagerInterface     $csrfTokenManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(
        EventFileSharingManager $fileSharingManager,
        Environment $twig,
        CsrfTokenManagerInterface $csrfTokenManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage
    ) {
        $this->fileSharingManager   = $fileSharingManager;
        $this->csrfTokenManager     = $csrfTokenManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
        $this->twig                 = $twig;
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
            $files = array_merge($files, $this->fileSharingManager->listFiles($event, EventFileShare::PURPOSE_MANAGEMENT));
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
     * @Route("/admin/event/{eid}/cloud", requirements={"eid": "\d+"}, name="admin_event_cloud")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     * @param Event $event
     * @return Response
     */
    public function manageEventCloudConfiguration(Event $event): Response
    {
        return $this->render(
            'event/admin/cloud-detail.html.twig',
            [
                'event' => $event,
            ]
        );
        
    }
}
