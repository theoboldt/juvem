<?php

namespace AppBundle\Controller\Event;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminParticipationController extends Controller
{
    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants", requirements={"eid": "\d+"}, name="event_participants_list")
     */
    public function listParticipantsAction($eid)
    {
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirect('event_miss');
        }


        $em                    = $this->getDoctrine()
                                      ->getManager();
        $query                 = $em->createQuery(
            'SELECT p
                   FROM AppBundle:Participation p
              WHERE p.event = :eid'
        )
                                    ->setParameter('eid', $eid);
        $participantEntityList = $query->getResult();

        return $this->render('event/participation/admin/participants-list.html.twig', array('event' => $event));
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/admin/event/{eid}/participants.json", requirements={"eid": "\d+"}, name="event_participants_list_data")
     */
    public function listParticipantsDataAction(Request $request)
    {
        $eid             = $request->get('eid');
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->redirect('event_miss');
        }

        $em                    = $this->getDoctrine()
                                      ->getManager();
        $query                 = $em->createQuery(
            'SELECT a
               FROM AppBundle:Participant a,
                    AppBundle:Participation p
              WHERE a.participation = p.pid
                AND p.event = :eid'
        )
                                    ->setParameter('eid', $eid);
        $participantEntityList = $query->getResult();

        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED);

        $participantList = array();
        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {
            $participation = $participant->getParticipation();

            $participantAge   = $event->getStartDate()
                                      ->diff($participant->getBirthday());
            $participantPhone = '';

            $phoneNumberUtil = PhoneNumberUtil::getInstance();

            /** @var PhoneNumber $phoneNumberEntity */
            foreach ($participation->getPhoneNumbers()
                                   ->getIterator() as $phoneNumberEntity) {
                /** @var \libphonenumber\PhoneNumber $phoneNumber */
                $phoneNumber = $phoneNumberEntity->getNumber();
                $participantPhone .= sprintf(
                    '<span class="label label-primary">%s</span> ',
                    $phoneNumberUtil->formatOutOfCountryCallingNumber($phoneNumber, 'DE')
                );
            }

            $participantAction = '';


            $participantList[] = array(
                'aid'       => $participant->getAid(),
                'pid'       => $participant->getParticipation()
                                           ->getPid(),
                'nameFirst' => $participant->getNameFirst(),
                'nameLast'  => $participant->getNameLast(),
                'age'       => $participantAge->format('%y'),
                'phone'     => $participantPhone,
                'status'    => $statusFormatter->formatMask($participant->getStatus(true)),
                'action'    => $participantAction
            );
        }

        return new JsonResponse($participantList);
    }


    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participation/{pid}", requirements={"eid": "\d+", "pid": "\d+"}, name="event_participation_detail")
     */
    public function ParticipationDetailAction(Request $request)
    {
        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $participation = $participationRepository->findOneBy(array('pid' => $request->get('pid')));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }
        $event = $participation->getEvent();

        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED);

        $buttonConfirmation = array(
            'entityName'   => 'Participation',
            'propertyName' => 'isConfirmed',
            'entityId'     => $participation->getPid(),
            'isEnabled'    => $participation->isConfirmed(),
            'buttons'      => array(
                'buttonEnable'  => array(
                    'label' => 'Bestätigen',
                    'glyph' => 'ok'
                ),
                'buttonDisable' => array(
                    'label' => 'Bestätigung zurücknehmen',
                    'glyph' => 'remove'
                )
            )
        );
        $buttonPayment      = array(
            'entityName'   => 'Participation',
            'propertyName' => 'isPaid',
            'entityId'     => $participation->getPid(),
            'isEnabled'    => $participation->isPaid(),
            'buttons'      => array(
                'buttonEnable'  => array(
                    'label' => 'Zahlungseingang vermerken',
                    'glyph' => 'ok'
                ),
                'buttonDisable' => array(
                    'label' => 'Zahlung zurücknehmen',
                    'glyph' => 'remove'
                )
            )
        );
        $phoneNumberList    = array();
        /** @var PhoneNumber $phoneNumberEntity */
        foreach ($participation->getPhoneNumbers()
                               ->getIterator() as $phoneNumberEntity) {
            /** @var \libphonenumber\PhoneNumber $phoneNumber */
            $phoneNumber       = $phoneNumberEntity->getNumber();
            $phoneNumberList[] = $phoneNumber;
        }

        return $this->render(
            'event/participation/admin/detail.html.twig',
            array('event'              => $event,
                  'participation'      => $participation,
                  'statusFormatter'    => $statusFormatter,
                  'phoneNumberList'    => $phoneNumberList,
                  'buttonConfirmation' => $buttonConfirmation,
                  'buttonPayment'      => $buttonPayment
            )
        );
    }


}
