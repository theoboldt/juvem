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


use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\ParticipantFilloutValue;
use AppBundle\Entity\ParticipantConnector;
use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\MoveParticipationType;
use AppBundle\Form\ParticipantType;
use AppBundle\Form\ParticipationAssignRelatedParticipantType;
use AppBundle\Form\ParticipationAssignUserType;
use AppBundle\Form\ParticipationBaseType;
use AppBundle\Form\ParticipationPhoneNumberList;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Manager\Payment\PaymentManager;
use AppBundle\Manager\Payment\PaymentSuggestionManager;
use AppBundle\Manager\RelatedParticipantsFinder;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class AdminParticipantConnectorController extends Controller
{


    /**
     * Perform a token visit
     *
     * @Route("/admin/event/{eid}/participation/{pid}/connectors/{aid}",
     *     requirements={"eid": "\d+", "pid": "\d+", "aid": "\d+"}, name="event_participation_connectors")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("participant", class="AppBundle:Participant", options={"id" = "aid"})
     * @Security("is_granted('participants_read', event)")
     */
    public function connectorsOverviewAction(Event $event, Participant $participant)
    {
        return $this->render(
            'event/participation/admin/participant-connectors.html.twig',
            [
                'event'       => $event,
                'participant' => $participant,
                'connectors'  => $participant->getConnectors(),
            ]
        );
    }


    /**
     * Perform a token visit
     *
     * @Route("/admin/t/{token}", requirements={"eid": "\d+", "token": "[0-9a-zA-Z]{32}"},
     *                            name="admin_connector_connect")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @param string $token
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function visitConnectorAction(string $token)
    {
        return new Response();
        return $this->redirectToRoute(
            'event_participation_detail',
            ['eid' => $event->getEid(), 'pid' => $participant->getParticipation()->getPid()]
        );
    }


}
