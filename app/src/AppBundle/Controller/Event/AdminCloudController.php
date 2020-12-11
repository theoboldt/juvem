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


use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventFileShare;
use AppBundle\InvalidTokenHttpException;
use AppBundle\JsonResponse;
use AppBundle\Manager\Filesharing\NextcloudManager;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * AdminCloudController
 *
 * @Security("is_granted('ROLE_ADMIN')")
 */
class AdminCloudController
{
    
    use DoctrineAwareControllerTrait;
    
    /**
     * security.csrf.token_manager
     *
     * @var CsrfTokenManagerInterface
     */
    private CsrfTokenManagerInterface $csrfTokenManager;
    
    /**
     * @var NextcloudManager|null
     */
    private ?NextcloudManager $nextcloudManager;
    
    /**
     * AdminCloudController constructor.
     *
     * @param NextcloudManager|null $nextcloudManager
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        ?NextcloudManager $nextcloudManager, CsrfTokenManagerInterface $csrfTokenManager, ManagerRegistry $doctrine
    )
    {
        $this->nextcloudManager = $nextcloudManager;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->doctrine         = $doctrine;
    }
    
    /**
     * @Route("/admin/event/{eid}/cloud/availability", requirements={"eid": "\d+"}, name="admin_event_cloud_availability")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     * @param Event $event
     * @return Response
     */
    public function hasCloudAction(Event $event): Response
    {
        $shares = $this->findSharesForEvent($event);
        
        if (count($shares)) {
            return new JsonResponse(['available' => true]);
        }
        return new JsonResponse(['available' => false]);
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
        
        if ($event->getShareDirectoryRootHref()) {
            return new JsonResponse([]);
        }
        $directory = $this->nextcloudManager->createUniqueEventRootDirectory(
            $event->getTitle(), $event->getStartDate()
        );
        $em        = $this->getDoctrine()->getManager();
        $event->setShareDirectoryRootHref($directory->getHref());
        $em->persist($event);
        $em->flush();
        
        $shareDirectory = $this->nextcloudManager->createEventTeamShare($directory);
        $share          = new EventFileShare(
            $event,
            EventFileShare::PURPOSE_TEAM,
            $shareDirectory->getFileId(),
            $shareDirectory->getHref(false),
            $shareDirectory->getName(),
            $shareDirectory->getName()
        );
        $em->persist($share);
        $em->flush();
        
        $shareDirectory = $this->nextcloudManager->createEventManagementShare($directory);
        $share          = new EventFileShare(
            $event,
            EventFileShare::PURPOSE_MANAGEMENT,
            $shareDirectory->getFileId(),
            $shareDirectory->getHref(false),
            $shareDirectory->getName(),
            $shareDirectory->getName()
        );
        $em->persist($share);
        $em->flush();
        
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
        
        $usersTeam       = [];
        $usersManagement = [];
        foreach ($event->getUserAssignments() as $userAssignment) {
            $cloudUserName = $userAssignment->getUser()->getCloudUsername();
            if ($cloudUserName) {
                if ($userAssignment->isAllowedCloudAccessTeam()) {
                    $usersTeam[] = $cloudUserName;
                }
                if ($userAssignment->isAllowedCloudAccessManagement()) {
                    $usersManagement[] = $cloudUserName;
                }
            }
        }
    
        $shares = $this->findSharesForEvent($event);
        foreach ($shares as $share) {
            if ($share->getPurpose() === EventFileShare::PURPOSE_TEAM) {
                $this->nextcloudManager->updateEventShareAssignments(
                    $share->getGroupName(),
                    $usersTeam
                );
            }
            if ($share->getPurpose() === EventFileShare::PURPOSE_MANAGEMENT) {
                $this->nextcloudManager->updateEventShareAssignments(
                    $share->getGroupName(),
                    $usersManagement
                );
            }
        }
    
        return new JsonResponse([]);
    }
}
