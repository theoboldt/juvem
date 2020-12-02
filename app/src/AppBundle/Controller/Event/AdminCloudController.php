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
use AppBundle\InvalidTokenHttpException;
use AppBundle\JsonResponse;
use AppBundle\Manager\Filesharing\NextcloudManager;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
     * Page for list of events
     *
     * @Route("/admin/event/{eid}/cloud/{token}/enable", requirements={"eid": "\d+", "token": ".*"}, name="admin_event_cloud_enable")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     * @param Event $event
     * @param string $token
     */
    public function enableCloudAction(Event $event, string $token)
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('cloud-' . $event->getEid(), $token))) {
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
        
        $share = $this->nextcloudManager->createEventShare(
            $event->getTitle(), $event->getStartDate(), $usersTeam, $usersManagement
        );
        
        $event->setCloudTeamDirectoryName($share->getNameTeam());
        $event->setCloudTeamDirectoryId($share->getTeamDirectoryId());
        $event->setCloudManagementDirectoryName($share->getNameManagement());
        $event->setCloudManagementDirectoryId($share->getManagementDirectoryId());
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($event);
        $em->flush();
        
        return new JsonResponse([]);
    }
    
    
}