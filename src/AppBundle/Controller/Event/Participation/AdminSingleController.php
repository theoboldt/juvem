<?php

namespace AppBundle\Controller\Event\Participation;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\ParticipationBaseType;
use AppBundle\Form\ParticipationPhoneNumberList;
use AppBundle\Form\PhoneNumberListType;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $participation = $participationRepository->findOneBy(array('pid' => $request->get('pid')));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }
        $event = $participation->getEvent();

        $form = $this->createFormBuilder()
                     ->add('action', HiddenType::class)
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->get('action')
                           ->getData();
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
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($participation);
            $em->flush();
        }

        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );
        $foodFormatter = new LabelFormatter();

        $phoneNumberList = array();
        /** @var PhoneNumber $phoneNumberEntity */
        foreach ($participation->getPhoneNumbers()
                               ->getIterator() as $phoneNumberEntity) {
            /** @var \libphonenumber\PhoneNumber $phoneNumber */
            $phoneNumber       = $phoneNumberEntity->getNumber();
            $phoneNumberList[] = $phoneNumber;
        }

        return $this->render(
            'event/participation/admin/detail.html.twig',
            array('event'           => $event,
                  'participation'   => $participation,
                  'foodFormatter'   => $foodFormatter,
                  'statusFormatter' => $statusFormatter,
                  'phoneNumberList' => $phoneNumberList,
                  'form'            => $form->createView()
            )
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
            throw new AccessDeniedHttpException('Invalid token');
        }

        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $participation = $participationRepository->findOneBy(array('pid' => $request->get('pid')));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }

        $participationManager = $this->get('app.participation_manager');
        $participationManager->mailParticipationConfirmed($participation, $participation->getEvent());

        return new JsonResponse(
            array(
                'success' => true
            )
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
        $repository    = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participation = $repository->findOneBy(array('pid' => $request->get('pid')));
        $event         = $participation->getEvent();

        $form = $this->createForm(ParticipationBaseType::class, $participation);
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
                'acquisitionFields' => $event->getAcquisitionAttributes(true, false),
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
        $repository    = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participation = $repository->findOneBy(array('pid' => $request->get('pid')));
        $event         = $participation->getEvent();

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
                'acquisitionFields' => $event->getAcquisitionAttributes(true, false),
            )
        );
    }
}
