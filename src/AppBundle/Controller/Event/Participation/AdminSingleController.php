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

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\ParticipantFilloutValue;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationRepository;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\ParticipantType;
use AppBundle\Form\ParticipationAssignRelatedParticipantType;
use AppBundle\Form\ParticipationAssignUserType;
use AppBundle\Form\ParticipationBaseType;
use AppBundle\Form\ParticipationPhoneNumberList;
use AppBundle\Form\ParticipationType;
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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminSingleController extends Controller
{
    /**
     * Details of one participation including all participants
     *
     * @Route("/admin/event/{eid}/participation/{pid}", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                  name="event_participation_detail")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
        public function participationDetailAction(Request $request)
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);

        $participation = $participationRepository->findDetailed($request->get('pid'));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }
        $event = $participation->getEvent();
        $this->denyAccessUnlessGranted('participants_read', $event);

        $formAction = $this->createFormBuilder()
                           ->add('action', HiddenType::class)
                           ->getForm();
        $formUser   = $this->createForm(ParticipationAssignUserType::class, $participation);

        $participationChanged = false;
        $formAction->handleRequest($request);
        if ($formAction->isSubmitted() && $formAction->isValid()) {
            $action = $formAction->get('action')->getData();
            switch ($action) {
                case 'delete':
                    $participation->setDeletedAt(new \DateTime());
                    break;
                case 'restore':
                    $participation->setDeletedAt(null);
                    break;
                case 'withdraw':
                    $participation->setIsWithdrawn(true);
                    break;
                case 'reactivate':
                    $participation->setIsWithdrawn(false);
                    break;
                case 'reject':
                    $participation->setIsRejected(true);
                    break;
                case 'rereject':
                    $participation->setIsRejected(false);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $participationChanged = true;
        } else {
            $formUser->handleRequest($request);
            if ($formUser->isSubmitted() && $formUser->isValid()) {
                $participationChanged = true;
            }
        }
        $em = $this->getDoctrine()->getManager();
    
        $formRelated = $this->createForm(
            ParticipationAssignRelatedParticipantType::class, null, ['eid' => $event->getEid()]
        );
        $formRelated->handleRequest($request);
        if ($formRelated->isSubmitted() && $formRelated->isValid()) {
            $oid = (int)$formRelated->get('oid')->getData();
            $relatedParticipant = $formRelated->get('related')->getData();
        
            $formRelatedUpdateFillout = function (\Traversable $fillouts) use (
                $oid, $em, $event, $relatedParticipant, &$participationChanged
            ) {
                /** @var Fillout $fillout */
                foreach ($fillouts as $fillout) {
                    $filloutValue = $fillout->getValue();
                    if ($fillout->getOid() === $oid && $filloutValue instanceof ParticipantFilloutValue) {
                        $this->denyAccessUnlessGranted('participants_edit', $event);
                        $filloutValue = $filloutValue->createWithParticipantSelected($relatedParticipant);
                        $fillout->setValue($filloutValue->getRawValue());
                        $em->persist($fillout);
                        $participationChanged = true;
                        return true;
                    }
                }
                return false;
            };
            if (!$formRelatedUpdateFillout($participation->getAcquisitionAttributeFillouts())) {
                foreach ($participation->getParticipants() as $participant) {
                    if ($formRelatedUpdateFillout($participant->getAcquisitionAttributeFillouts())) {
                        break;
                    }
                }
            }
        }

        if ($participationChanged) {
            $this->denyAccessUnlessGranted('participants_edit', $event);
            $em->persist($participation);
            $em->flush();
            return $this->redirectToRoute(
                'event_participation_detail',
                [
                    'eid' => $event->getEid(),
                    'pid' => $participation->getPid(),
                ]
            );
        }

        $statusFormatter = ParticipantStatus::formatter();
        $foodFormatter   = new LabelFormatter();

        $phoneNumberList = array();
        /** @var PhoneNumber $phoneNumberEntity */
        foreach ($participation->getPhoneNumbers() as $phoneNumberEntity) {
            /** @var \libphonenumber\PhoneNumber $phoneNumber */
            $phoneNumber       = $phoneNumberEntity->getNumber();
            $phoneNumberList[] = $phoneNumber;
        }
        $commentManager = $this->container->get('app.comment_manager');

        $similarParticipants = [];
        /** @var Participant $participant */
        foreach ($participation->getParticipants() as $participant) {
            $similarParticipants[$participant->getAid()] = $participationRepository->relatedParticipants($participant);
        }

        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->get('app.payment_manager');
        /** @var PaymentSuggestionManager $paymentSuggestions */
        $paymentSuggestionsManager = $this->get('app.payment_suggestion_manager');
        $priceSuggestions          = $paymentSuggestionsManager->priceSuggestionsForParticipation(
            $participant->getParticipation()
        );
        $paymentSuggestions          = $paymentSuggestionsManager->paymentSuggestionsForParticipation(
            $participant->getParticipation()
        );

        return $this->render(
            'event/participation/admin/detail.html.twig',
            [
                'commentManager'      => $commentManager,
                'paymentManager'      => $paymentManager,
                'priceSuggestions'    => $priceSuggestions,
                'paymentSuggestions'  => $paymentSuggestions,
                'event'               => $event,
                'participation'       => $participation,
                'similarParticipants' => $similarParticipants,
                'foodFormatter'       => $foodFormatter,
                'statusFormatter'     => $statusFormatter,
                'phoneNumberList'     => $phoneNumberList,
                'formAction'          => $formAction->createView(),
                'formAssignUser'      => $formUser->createView(),
                'formRelated'         => $formRelated->createView(),
            ]
        );
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/event/participation/confirm", name="event_participation_confirm_mail")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function participationConfirmAction(Request $request)
    {
        $token = $request->get('_token');
        $pid   = $request->get('pid');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken($pid)) {
            throw new InvalidTokenHttpException();
        }

        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);

        $participation = $participationRepository->findOneBy(array('pid' => $request->get('pid')));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }
        $event = $participation->getEvent();
        $this->denyAccessUnlessGranted('participants_edit', $event);

        $participationManager = $this->get('app.participation_manager');
        $participationManager->mailParticipationConfirmed($participation, $event);

        return new JsonResponse(
            array(
                'success' => true,
            )
        );
    }

    /**
     * Create new participation by admin
     *
     * @Route("/admin/event/{eid}/participation/create", requirements={"eid": "\d+"}, name="admin_participation_create")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('participants_edit', event)")
     * @param Event $event
     * @param Request $request
     * @return Response
     */
    public function createParticipationAction(Event $event, Request $request) {
        $participation = new Participation($event);

        $form = $this->createForm(
            ParticipationBaseType::class,
            $participation,
            [
                ParticipationBaseType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipationBaseType::ACQUISITION_FIELD_PRIVATE => true,
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \AppBundle\Entity\User $user */
            $user = $this->getUser();

            $managedParticipation = $this->get('app.participation_manager')->receiveParticipationRequest(
                $participation, $user
            );

            //$participationManager = $this->get('app.participation_manager');
            //$participationManager->mailParticipationRequested($participation, $event); //no mails

            return $this->redirectToRoute(
                'event_participation_detail', ['eid' => $event->getEid(), 'pid' => $managedParticipation->getPid()]
            );
        }

        $user           = $this->getUser();
        $participations = [];
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        return $this->render(
            'event/participation/admin/add-participation.html.twig',
            [
                'event'                          => $event,
                'acquisitionFieldsParticipation' => $event->getAcquisitionAttributes(true, false, false, true, true),
                'participations'                 => $participations,
                'acquisitionFieldsParticipant'   => $event->getAcquisitionAttributes(false, true, false, true, true),
                'form'                           => $form->createView(),
            ]
        );
    }

    /**
     * Page edit an participation
     *
     * @Route("/admin/event/{eid}/participation/{pid}/edit", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                       name="admin_edit_participation")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editParticipationAction(Request $request)
    {
        $repository    = $this->getDoctrine()->getRepository(Participation::class);
        /** @var Participation $participation */
        $participation = $repository->findOneBy(array('pid' => $request->get('pid')));
        $event         = $participation->getEvent();
        $this->denyAccessUnlessGranted('participants_edit', $event);

        $form = $this->createForm(
            ParticipationBaseType::class,
            $participation,
            [
                ParticipantType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipantType::ACQUISITION_FIELD_PRIVATE => true,
            ]
        );
        $form->remove('phoneNumbers');
        $form->remove('participants');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em                   = $this->getDoctrine()->getManager();
            $managedParticipation = $em->merge($participation);
            $em->persist($managedParticipation);
            $em->flush();
            $this->addFlash(
                'success',
                'Die Änderungen wurden gespeichert.'
            );
            return $this->redirectToRoute(
                'event_participation_detail', array('eid' => $event->getEid(), 'pid' => $participation->getPid())
            );
        }
        return $this->render(
            'event/participation/edit-participation.html.twig',
            array(
                'adminView'         => true,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(true, false, true, true),
            )
        );
    }

    /**
     * Page edit an participation
     *
     * @Route("/admin/event/{eid}/participation/{pid}/phone", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                        name="admin_edit_phonenumbers")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editPhoneNumbersAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository(Participation::class);
        /** @var Participation $participation */
        $participation = $repository->findOneBy(array('pid' => $request->get('pid')));
        $event         = $participation->getEvent();
        $this->denyAccessUnlessGranted('participants_edit', $event);

        $originalPhoneNumbers = new ArrayCollection();
        foreach ($participation->getPhoneNumbers() as $number) {
            $originalPhoneNumbers->add($number);
        }

        $form = $this->createForm(ParticipationPhoneNumberList::class, $participation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($originalPhoneNumbers as $number) {
                if (false === $participation->getPhoneNumbers()->contains($number)) {
                    $participation->getPhoneNumbers()->removeElement($number);
                    $em->remove($number);
                }
            }
            /** @var PhoneNumber $number */
            foreach ($participation->getPhoneNumbers() as $number) {
                $number->setParticipation($participation);
            }

            $em->persist($participation);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen wurden gespeichert.'
            );
            return $this->redirectToRoute(
                'event_participation_detail', array('eid' => $event->getEid(), 'pid' => $participation->getPid())
            );
        }
        return $this->render(
            'event/participation/edit-phone-numbers.html.twig',
            array(
                'adminView'         => true,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(true, false, false, true, true),
            )
        );
    }

    /**
     * Page edit an participant
     *
     * @Route("/admin/event/{eid}/participation/{pid}/participant/{aid}", requirements={"eid": "\d+", "pid": "\d+",
     *                                                                    "aid": "\d+"}, name="admin_edit_participant")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editParticipantAction($eid, $pid, $aid, Request $request)
    {
        $repository    = $this->getDoctrine()->getRepository(Participant::class);
        /** @var Participant $participation */
        $participant   = $repository->findOneBy(array('aid' => $aid));
        $participation = $participant->getParticipation();
        $event         = $participation->getEvent();
        $this->denyAccessUnlessGranted('participants_edit', $event);

        $form = $this->createForm(
            ParticipantType::class,
            $participant,
            [
                ParticipantType::PARTICIPATION_FIELD       => $participation,
                ParticipantType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipantType::ACQUISITION_FIELD_PRIVATE => true,
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($participant);
            $em->flush();

            if ($participant->getGender() == Participant::TYPE_GENDER_FEMALE) {
                $message = 'dem Teilnehmerin ';
            } else {
                $message = 'der Teilnehmer ';
            }
            $this->addFlash(
                'success',
                'Die Änderungen an '.$message.$participant->getNameFirst().' wurden gespeichert.'
            );

            return $this->redirectToRoute(
                'event_participation_detail', array('eid' => $eid, 'pid' => $pid)
            );
        }
        return $this->render(
            'event/participation/edit-participant.html.twig',
            array(
                'adminView'         => true,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'participant'       => $participant,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, true, false, true, true),
            )
        );
    }

    /**
     * Page add a participant
     *
     * @Route("/admin/event/{eid}/participation/{pid}/participant/add", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                                  name="admin_add_participant")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function addParticipantAction($eid, $pid, Request $request)
    {
        $repository    = $this->getDoctrine()->getRepository(Participation::class);
        /** @var Participation $participation */
        $participation = $repository->findOneBy(array('pid' => $pid));
        $participant   = new Participant();
        $participant->setParticipation($participation);
        $event = $participation->getEvent();
        $this->denyAccessUnlessGranted('participants_edit', $event);

        $form = $this->createForm(
            ParticipantType::class,
            $participant,
            [
                ParticipantType::PARTICIPATION_FIELD       => $participation,
                ParticipantType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipantType::ACQUISITION_FIELD_PRIVATE => true,
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($participant);
            $em->flush();

            if ($participant->getGender() == Participant::TYPE_GENDER_FEMALE) {
                $message = 'Der Teilnehmerin ';
            } else {
                $message = 'Die Teilnehmer ';
            }
            $this->addFlash(
                'success',
                $message.' '.$participant->getNameFirst().' wurde hinzugefügt.'
            );
            return $this->redirectToRoute(
                'event_participation_detail', array('eid' => $eid, 'pid' => $pid)
            );
        }
        return $this->render(
            'event/participation/add-participant.html.twig',
            array(
                'adminView'         => true,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'participant'       => $participant,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, true, false, true, true),
            )
        );
    }

    /**
     * Participant detail page
     *
     * @Route("/admin/event/{eid}/participant/{aid}", requirements={"eid": "\d+", "aid": "\d+"},
     *                                                name="admin_participant_detail")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("fillout", class="AppBundle:Participant", options={"id" = "aid"})
     * @Security("is_granted('participants_read', event)")
     */
    public function participantDetailAction(Event $event, Participant $participant)
    {
        return $this->redirectToRoute(
            'event_participation_detail', ['eid' => $event->getEid(), 'pid' => $participant->getParticipation()->getPid()]
        );
    }

    /**
     * Get proposal
     *
     * @Route("/admin/event/{eid}/participant_proposal/{oid}", requirements={"eid": "\d+", "oid": "\d+"},
     *                                                                  name="admin_event_participant_proposal")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("fillout", class="AppBundle\Entity\AcquisitionAttribute\Fillout", options={"id" = "oid"})
     * @Security("is_granted('participants_read', event)")
     */
    public function relatedParticipantProposalAction(Event $event, Fillout $fillout) {
        $filloutValue = $fillout->getValue();
        if (!$filloutValue instanceof ParticipantFilloutValue) {
            throw new NotFoundHttpException('Provided fillout is not a participant fillout type');
        }

        /** @var RelatedParticipantsFinder $repository */
        $finder = $this->get('app.related_participants_finder');
        $participants = $finder->proposedParticipants($fillout);

        $result = [];
        foreach ($participants as $participant) {
            $result[] = [
                'aid'       => $participant->getAid(),
                'firstName' => $participant->getNameFirst(),
                'lastName'  => $participant->getNameLast(),
                'age'       => $participant->getYearsOfLifeAtEvent(),
            ];
        }

        return new JsonResponse(['rows' => $result, 'success' => true]);
    }

}
