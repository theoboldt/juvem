<?php

namespace AppBundle\Controller\Event\Participation;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\AttendanceList;
use AppBundle\Entity\AttendanceListFillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Form\AttendanceListType;
use AppBundle\InvalidTokenHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAttendanceListController extends Controller
{
    /**
     * @Route("/admin/event/{eid}/attendance", requirements={"eid": "\d+"}, name="event_attendance_lists")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listAttendanceListsAction($eid)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        return $this->render('event/attendance/list.html.twig', ['event' => $event]);
    }

    /**
     * @see listAttendanceListsAction()
     * @Route("/admin/event/{eid}/attendance-lists.json", requirements={"eid": "\d+"},
     *                                                    name="event_attendance_lists_data")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listAttendanceListsDataAction(Request $request)
    {
        $eid        = $request->get('eid');
        $repository = $this->getDoctrine()->getRepository('AppBundle:AttendanceList');

        $result = $repository->findBy(['event' => $eid]);

        return new JsonResponse(
            array_map(
                function (AttendanceList $list) use ($eid) {
                    $modifiedAt = '';
                    if ($list->getModifiedAt()) {
                        $modifiedAt = $list->getModifiedAt()->format(Event::DATE_FORMAT_DATE_TIME);
                    }

                    return [
                        'tid'               => $list->getTid(),
                        'eid'               => $eid,
                        'title'             => $list->getTitle(),
                        'createdAt'         => $list->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME),
                        'modifiedAt'        => $modifiedAt,
                        'isPublicTransport' => $list->getIsPublicTransport() ? 'ja' : 'nein',
                        'isPaid'            => $list->getIsPaid() ? 'ja' : 'nein',
                    ];
                }, $result
            )
        );
    }

    /**
     * Create a new attendance list
     *
     * @Route("/admin/event/{eid}/attendance/new", requirements={"eid": "\d+"}, name="event_attendance_list_new")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function newAction($eid, Request $request)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $list = new AttendanceList();
        $list->setEvent($event);

        $form = $this->createForm(AttendanceListType::class, $list);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($list);
            $em->flush();

            return $this->redirectToRoute('event_attendance_lists', ['eid' => $eid]);
        }

        return $this->render('/event/attendance/new.html.twig', ['form' => $form->createView(), 'event' => $event]);
    }

    /**
     * Edit an attendance list
     *
     * @Route("/admin/event/{eid}/attendance/{tid}/edit", requirements={"eid": "\d+", "tid": "\d+"},
     *                                                    name="event_attendance_list_edit")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editAction($eid, $tid, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:AttendanceList');

        $list = $repository->findOneBy(['tid' => $tid]);
        if (!$list) {
            return $this->render(
                'event/public/miss.html.twig', [], new Response(null, Response::HTTP_NOT_FOUND)
            );
        }
        $event = $list->getEvent();
        $form  = $this->createForm(AttendanceListType::class, $list);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($list);
            $em->flush();

            return $this->redirectToRoute('event_attendance_lists', ['eid' => $event->getEid()]);
        }

        return $this->render(
            '/event/attendance/edit.html.twig', ['form' => $form->createView(), 'list' => $list, 'event' => $event]
        );
    }

    /**
     * Edit an attendance list
     *
     * @Route("/admin/event/{eid}/attendance/{tid}", requirements={"eid": "\d+", "tid": "\d+"},
     *                                                    name="event_attendance_details")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function detailAction($eid, $tid, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:AttendanceList');

        $list = $repository->findOneBy(['tid' => $tid]);
        if (!$list) {
            return $this->render(
                'event/public/miss.html.twig', [], new Response(null, Response::HTTP_NOT_FOUND)
            );
        }
        $event = $list->getEvent();

        return $this->render(
            '/event/attendance/detail.html.twig', ['list' => $list, 'event' => $event]
        );
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/admin/event/{eid}/attendance/{tid}/participants.json", requirements={"eid": "\d+", "tid": "\d+"},
     *                                                                 name="event_attendance_list_participants_data")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listParticipationsAction($eid, $tid, Request $request)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $event           = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }
        $participantEntityList = $eventRepository->participantsList($event, null, false, false);
        $filloutRepository     = $this->getDoctrine()->getRepository('AppBundle:AttendanceListFillout');
        $filloutList           = [];
        foreach ($filloutRepository->findBy(['attendanceList' => $tid]) as $fillout) {
            $filloutList[$fillout->getParticipant()->getAid()] = $fillout;
        }

        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );

        $participantList = [];
        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {
            if (isset($filloutList[$participant->getAid()])) {
                /** @var AttendanceListFillout $fillout */
                $fillout = $filloutList[$participant->getAid()];
            } else {
                $fillout = null;
            }
            $did = $fillout ? $fillout->getDid() : false;
            $aid = $participant->getAid();

            $participantEntry = [
                'tid'               => (int)$tid,
                'did'               => (int)$did,
                'aid'               => (int)$aid,
                'pid'               => $participant->getParticipation()->getPid(),
                'nameFirst'         => $participant->getNameFirst(),
                'nameLast'          => $participant->getNameLast(),
                'status'            => $statusFormatter->formatMask($participant->getStatus(true)),
                'isAttendant'       => (int)($fillout ? $fillout->getIsAttendant() : 0),
                'isPaid'            => (int)($fillout ? $fillout->getIsPaid() : 0),
                'isPublicTransport' => (int)($fillout ? $fillout->getIsPublicTransport() : 0),
                'comment'           => $fillout ? $fillout->getComment() : false
            ];

            $participantList[] = $participantEntry;
        }

        return new JsonResponse($participantList);
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/admin/attendance/{tid}/change", requirements={"tid": "\d+"}, name="event_attendance_list_change")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function changeAttendanceListEntryAction($tid, Request $request)
    {
        $token    = $request->get('_token');
        $aid      = $request->get('aid');
        $property = $request->get('property');
        $valueNew = $request->get('valueNew');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken($tid)) {
            throw new InvalidTokenHttpException();
        }

        $repositoryParticipant = $this->getDoctrine()->getRepository('AppBundle:Participant');
        $participant           = $repositoryParticipant->findOneBy(['aid' => $aid]);
        $repositoryList        = $this->getDoctrine()->getRepository('AppBundle:AttendanceList');
        $list                  = $repositoryList->findOneBy(['tid' => $tid]);
        $repositoryFillout     = $this->getDoctrine()->getRepository('AppBundle:AttendanceListFillout');

        if (!$participant || !$list) {
            throw new \InvalidArgumentException('Unknown participant or list transmitted');
        }

        $fillout = $repositoryFillout->findFillout($participant, $list, true);
        switch ($property) {
            case 'isAttendant':
                $fillout->setIsAttendant($valueNew);
                break;
            case 'isPaid':
                $fillout->setIsPaid($valueNew);
                break;
            case 'isPublicTransport':
                $fillout->setIsPublicTransport($valueNew);
                break;
            default:
                throw new \InvalidArgumentException('Unknown property transmitted');
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($fillout);
        $em->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
