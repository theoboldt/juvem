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

use AppBundle\Entity\ChangeTracking\EntityChange;
use AppBundle\Entity\ChangeTracking\EntityChangeRepository;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Security\EventVoter;
use AppBundle\SerializeJsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class EntityChangeTrackingController extends Controller
{
    /**
     * Get list of changes for transmitted entity
     *
     * @Route("/admin/changes/{classDescriptor}/{entityId}.json",
     *     requirements={"classDescriptor": "([a-zA-Z0-9_\.]+)", "entityId": "(\d+)"}, name="admin_change_overview")
     * @Security("has_role('ROLE_ADMIN')")
     * @param string $classDescriptor
     * @param int $entityId
     * @return Response
     */
    public function ListEntityChangesAction(string $classDescriptor, int $entityId): Response
    {
        /** @var EntityChangeRepository $repository */
        $repository = $this->getDoctrine()->getRepository(EntityChange::class);
        $className  = EntityChangeRepository::convertRouteToClassName($classDescriptor);
        
        $relatedRepository = $this->getDoctrine()->getRepository($className);
        $relatedEntity     = $relatedRepository->find($entityId);
        if (!$relatedEntity) {
            throw new NotFoundHttpException('Failed to find related entity');
        }
        
        $securityChecker   = $this->get('security.authorization_checker');
        $securityAttribute = 'read';
        
        if ($relatedEntity instanceof Participation || $relatedEntity instanceof Participant) {
            if (!$securityChecker->isGranted(EventVoter::PARTICIPANTS_READ, $relatedEntity->getEvent())) {
                throw new AccessDeniedHttpException('Requested change list for participant/participation');
            }
        } else {
            if (!$securityChecker->isGranted($securityAttribute, $relatedEntity)) {
                throw new AccessDeniedHttpException('Requested change list for incorrect entity');
            }
        }
        
        $changes = $repository->findAllByClassAndId($className, $entityId);
        
        $result = ['changes' => $changes];
        
        if ($relatedEntity instanceof SpecifiesChangeTrackingStorableRepresentationInterface) {
            $result['title'] = $relatedEntity->getChangeTrackingStorableRepresentation();
        }
        
        return new SerializeJsonResponse($result);
    }
}