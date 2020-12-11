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
use AppBundle\Manager\Filesharing\EventFileSharingManager;
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
     * @var EventFileSharingManager
     */
    private EventFileSharingManager $fileSharingManager;
    
    /**
     * AdminCloudController constructor.
     *
     * @param EventFileSharingManager $fileSharingManager
     * @param CsrfTokenManagerInterface $csrfTokenManager
     */
    public function __construct(
        EventFileSharingManager $fileSharingManager, CsrfTokenManagerInterface $csrfTokenManager
    )
    {
        $this->fileSharingManager = $fileSharingManager;
        $this->csrfTokenManager   = $csrfTokenManager;
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
}
