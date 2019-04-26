<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event\Participation;

use AppBundle\Entity\AcquisitionAttribute\AcquisitionAttributeRepository;
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminTypeaheadProviderController extends Controller
{
    /**
     * Collects and provides typeahead values for transmitted event
     *
     * @Route("/admin/event/{eid}/typeahead/proposals.json", requirements={"eid": "\d+"}, name="admin_event_typeahead_proposals")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('participants_read', event)")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function participationConfirmAction(Event $event, Request $request)
    {
        /** @var ParticipationRepository $repository */
        $repository   = $this->getDoctrine()->getRepository(Participation::class);
        $lastModified = $repository->getLastModificationForParticipationOrParticipantEvent($event);
        $expectedEtag = sha1($event->getEid() . '-' . $lastModified);
        foreach ($request->getETags() as $givenEtag) {
            if (trim($givenEtag, " \t\n\r\0\x0B\"") === $expectedEtag || $expectedEtag === '*') {
                return Response::create('', Response::HTTP_NOT_MODIFIED);
            }
        }
        $participation = $repository->getFieldProposals();
        
        /** @var AcquisitionAttributeRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Attribute::class);
        $attributes = $repository->findTextualAttributesForEvent($event);
        $fillouts     = $repository->findAllAttributeValuesForFillouts($attributes);
        
        $response = new JsonResponse(['proposals' => array_merge($fillouts, $participation)]);
        $response->setLastModified($lastModified ? new \DateTime($lastModified) : null)
                 ->setMaxAge(1 * 60 * 60)
                 ->setETag($expectedEtag)
                 ->setPublic()
                 ->isNotModified($request);
        return $response;
    }
    
}
