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

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\ParticipantType;
use AppBundle\Form\ParticipationBaseType;
use AppBundle\Form\ParticipationPhoneNumberList;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PublicManagementController extends Controller
{
    /**
     * Page for list of events
     *
     * @Route("/participation", name="public_participations")
     * @Security("has_role('ROLE_USER')")
     */
    public function listParticipationsAction()
    {
        return $this->render('event/participation/public/participations-list.twig');
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/participations.json", name="public_participations_list_data")
     * @Security("has_role('ROLE_USER')")
     */
    public function listParticipantsDataAction(Request $request)
    {
        $statusFormatter   = ParticipantStatus::formatter();

        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');

        $user = $this->getUser();

        $participationList = $participationRepository->findBy(array('assignedUser' => $user->getUid()));

        $participationListResult = array();
        /** @var Participant $participant */
        foreach ($participationList as $participation) {
            $event = $participation->getEvent();


            $eventStartDate = $event->getStartDate()->format(Event::DATE_FORMAT_DATE);
            if ($event->hasEndDate()) {
                $eventEndDate = $event->getEndDate()->format(Event::DATE_FORMAT_DATE);
            } else {
                $eventEndDate = $eventStartDate;
            }
            if ($event->hasStartTime()) {
                $eventStartDate .= ' ' . $event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            }
            if ($event->hasEndTime()) {
                $eventEndDate .= ' ' . $event->getEndTime()->format(Event::DATE_FORMAT_TIME);
            } elseif ($event->hasStartTime()) {
                $eventEndDate .= ' ' . $event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            }

            $eventTime = sprintf(
                '%s - %s',
                $eventStartDate,
                $eventEndDate
            );

            $participantsString = '';
            foreach ($participation->getParticipants() as $participant) {
                $participantStatusText = $statusFormatter->formatMask($participant->getStatus(true));
                if ($participant->getDeletedAt()) {
                    $participantStatusText .= ' <span class="label label-danger">gelöscht</span>';
                }

                $participantsString .= sprintf(
                    ' %s %s', $participant->getNameFirst(), $participantStatusText
                );
            }

            $participationListResult[] = array(
                'pid'          => $participation->getPid(),
                'eventTitle'   => $event->getTitle(),
                'eventTime'    => $eventTime,
                'participants' => $participantsString
            );
        }

        return new JsonResponse($participationListResult);
    }

    /**
     * Detail page for a users participation
     *
     * @ParamConverter("participation", class="AppBundle:Participation", options={"id" = "pid"})
     * @Route("/participation/{pid}", requirements={"pid": "\d+"}, name="public_participation_detail")
     * @Security("has_role('ROLE_USER')")
     */
    public function participationDetailedAction(Request $request, Participation $participation)
    {

        $statusFormatter   = ParticipantStatus::formatter();
        $user              = $this->getUser();
        $participationUser = $participation->getAssignedUser();

        if (!$participationUser || $participationUser->getUid() != $user->getUid()) {
            throw new AccessDeniedHttpException('Participation is related to another user');
        }

        $form = $this->createFormBuilder()
                     ->add('aid', HiddenType::class)
                     ->add('action', HiddenType::class)
                     ->add('value', HiddenType::class)
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action   = $form->get('action')->getData();
            $aid      = $form->get('aid')->getData();
            $newValue = !$form->get('value')->getData();

            /** @var Participant $participationParticipant */
            foreach ($participation->getParticipants() as $participationParticipant) {
                if ($participationParticipant->getAid() == $aid) {
                    $participant = $participationParticipant;
                }
            }
            if (isset($participant)) {
                switch ($action) {
                    case 'withdraw':
                        if ($participant->isWithdrawn()) {
                            if ($newValue) {
                                $this->addFlash(
                                    'success',
                                    'Die Anmeldung wurde von uns bereits als zurückgezogen markiert. Es ist keine weitere Aktion nötig.'
                                );
                            } else {
                                $this->addFlash(
                                    'danger',
                                    'Die Anmeldung wurde von uns bereits als zurückgezogen markiert. Wenn Sie die Anmeldung diesen Teilnehmers reaktivieren möchten, wenden Sie sich in diesem Fall bitte direkt an das Jugendwerk.'
                                );
                            }
                        } else {
                            $participant->setIsWithdrawRequested($newValue);
                            $em                   = $this->getDoctrine()->getManager();
                            $managedParticipation = $em->merge($participation);

                            $em->persist($managedParticipation);
                            $em->flush();
                            if ($newValue) {
                                $this->addFlash(
                                    'success',
                                    'Ihre Anfrage zur Zurücknahme dieser Anmeldung wurde registiert und wird demnächst von uns bearbeitet. Wenn sich der Status der Anmeldung des betroffenen Teilnehmers nicht innerhalb einiger Tage ändert, wenden Sie sich bitte direkt an das Jugendwerk.'
                                );
                            } else {
                                $this->addFlash(
                                    'success',
                                    'Sie haben ihre Anfrage auf Zurücknahme dieser Anmeldung entfernt. Die Anmeldung ist damit wieder gültig.'
                                );
                            }
                        }
                        break;

                }

                return $this->redirectToRoute('public_participation_detail', array('pid' => $participation->getPid()));
            }

        }

        return $this->render(
            'event/participation/public/detail.html.twig',
            array(
                'form'            => $form->createView(),
                'participation'   => $participation,
                'event'           => $participation->getEvent(),
                'statusFormatter' => $statusFormatter
            )
        );
    }

    /**
     * Page edit an participation
     *
     * @ParamConverter("participation", class="AppBundle:Participation", options={"id" = "pid"})
     * @Route("/participation/{pid}/edit/participation", requirements={"pid": "\d+"}, name="public_edit_participation")
     * @Security("has_role('ROLE_USER')")
     */
    public function editParticipationAction(Request $request, Participation $participation)
    {
        $user              = $this->getUser();
        $event             = $participation->getEvent();
        $participationUser = $participation->getAssignedUser();

        if (!$participationUser || $participationUser->getUid() != $user->getUid()
        ) {
            throw new AccessDeniedHttpException('Participation is related to another user');
        }

        $form = $this->createForm(
            ParticipationBaseType::class,
            $participation,
            [
                ParticipantType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipantType::ACQUISITION_FIELD_PRIVATE => false,
            ]        );
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
            return $this->redirectToRoute('public_participation_detail', array('pid' => $participation->getPid()));
        }
        return $this->render(
            'event/participation/edit-participation.html.twig',
            array(
                'adminView'         => false,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'acquisitionFields' => $event->getAcquisitionAttributes(true, false),
                'event'             => $event
            )
        );
    }

    /**
     * Page edit phone numbers
     *
     * @ParamConverter("participation", class="AppBundle:Participation", options={"id" = "pid"})
     * @Route("/participation/{pid}/edit/phone", requirements={"pid": "\d+"}, name="public_edit_phonenumbers")
     * @Security("has_role('ROLE_USER')")
     */
    public function editPhoneNumbersAction(Request $request, Participation $participation)
    {
        $user              = $this->getUser();
        $event             = $participation->getEvent();
        $participationUser = $participation->getAssignedUser();

        if (!$participationUser || $participationUser->getUid() != $user->getUid()
        ) {
            throw new AccessDeniedHttpException('Participation is related to another user');
        }

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
                'public_participation_detail', array('pid' => $participation->getPid())
            );
        }
        return $this->render(
            'event/participation/edit-phone-numbers.html.twig',
            array(
                'adminView'         => false,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(true, false),
            )
        );
    }

    /**
     * Page edit a participation
     *
     * @ParamConverter("participant", class="AppBundle:Participant", options={"id" = "aid"})
     * @Route("/participation/{pid}/edit/participant/{aid}", requirements={"pid": "\d+", "aid": "\d+"},
     *                                                       name="public_edit_participant")
     * @Security("has_role('ROLE_USER')")
     */
    public function editParticipantAction($pid, Participant $participant, Request $request)
    {
        $user              = $this->getUser();
        $participation     = $participant->getParticipation();
        $event             = $participation->getEvent();
        $participationUser = $participation->getAssignedUser();

        if (!$participationUser || $participationUser->getUid() != $user->getUid()
        ) {
            throw new AccessDeniedHttpException('Participation is related to another user');
        }

        $form = $this->createForm(
            ParticipantType::class,
            $participant,
            [
                ParticipantType::PARTICIPATION_FIELD       => $participation,
                ParticipantType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipantType::ACQUISITION_FIELD_PRIVATE => false,
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($participant);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen wurden gespeichert.'
            );
            return $this->redirectToRoute(
                'public_participation_detail', array('pid' => $participation->getPid())
            );
        }
        return $this->render(
            'event/participation/edit-participant.html.twig',
            array(
                'adminView'         => false,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'participant'       => $participant,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, true, true, false),
            )
        );
    }

    /**
     * Page add a participation
     *
     * @ParamConverter("participation", class="AppBundle:Participation", options={"id" = "pid"})
     * @Route("/participation/{pid}/edit/participant/add", requirements={"pid": "\d+"}, name="public_add_participant")
     * @Security("has_role('ROLE_USER')")
     */
    public function addParticipantAction(Request $request, Participation $participation)
    {
        $user          = $this->getUser();
        $participant   = new Participant();
        $participant->setParticipation($participation);
        $event             = $participation->getEvent();
        $participationUser = $participation->getAssignedUser();

        if (!$participationUser || $participationUser->getUid() != $user->getUid()
        ) {
            throw new AccessDeniedHttpException('Participation is related to another user');
        }

        $form = $this->createForm(
            ParticipantType::class,
            $participant,
            [
                ParticipantType::PARTICIPATION_FIELD       => $participation,
                ParticipantType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipantType::ACQUISITION_FIELD_PRIVATE => false,
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
                'public_participation_detail', array('pid' => $participation->getPid())
            );
        }

        return $this->render(
            'event/participation/add-participant.html.twig',
            array(
                'adminView'         => false,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'participant'       => $participant,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, true),
            )
        );
    }
}