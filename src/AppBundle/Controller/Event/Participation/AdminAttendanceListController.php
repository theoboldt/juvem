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
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AttendanceList;
use AppBundle\Entity\AttendanceListFillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Form\AttendanceListType;
use AppBundle\InvalidTokenHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAttendanceListController extends Controller
{
    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/attendance", requirements={"eid": "\d+"}, name="event_attendance_lists")
     * @Security("is_granted('participants_read', event)")
     */
    public function listAttendanceListsAction(Event $event)
    {
        return $this->render('event/attendance/list.html.twig', ['event' => $event]);
    }

    /**
     * @see listAttendanceListsAction()
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/attendance-lists.json", requirements={"eid": "\d+"},
     *                                                    name="event_attendance_lists_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function listAttendanceListsDataAction(Event $event)
    {
        $repository = $this->getDoctrine()->getRepository(AttendanceList::class);
        $eid        = $event->getEid();

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
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/attendance/new", requirements={"eid": "\d+"}, name="event_attendance_list_new")
     * @Security("is_granted('participants_read', event)")
     */
    public function newAction(Event $event, Request $request)
    {
        $list = new AttendanceList();
        $list->setEvent($event);

        $form = $this->createForm(AttendanceListType::class, $list);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($list);
            $em->flush();

            return $this->redirectToRoute('event_attendance_lists', ['eid' => $event->getEid()]);
        }

        return $this->render('/event/attendance/new.html.twig', ['form' => $form->createView(), 'event' => $event]);
    }

    /**
     * Edit an attendance list
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/attendance/{tid}/edit", requirements={"eid": "\d+", "tid": "\d+"},
     *                                                    name="event_attendance_list_edit")
     * @Security("is_granted('participants_read', event)")
     */
    public function editAction(Event $event, $tid, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository(AttendanceList::class);

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
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("list", class="AppBundle:AttendanceList", options={"id" = "tid"})
     * @Route("/admin/event/{eid}/attendance/{tid}", requirements={"eid": "\d+", "tid": "\d+"},
     *                                                    name="event_attendance_details")
     * @Security("is_granted('participants_read', event)")
     */
    public function detailAction(Event $event, AttendanceList $list, Request $request)
    {
        return $this->render(
            '/event/attendance/detail.html.twig', ['list' => $list, 'event' => $event]
        );
    }

    /**
     * Data provider for events participants list grid
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/attendance/{tid}/participants.json", requirements={"eid": "\d+", "tid": "\d+"},
     *                                                                 name="event_attendance_list_participants_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipationsAction(Event $event, $tid, Request $request)
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participantEntityList   = $participationRepository->participantsList($event, null, false, false);
        $filloutRepository       = $this->getDoctrine()->getRepository(AttendanceListFillout::class);
        $filloutList             = [];
        /** @var Fillout $fillout */
        foreach ($filloutRepository->findBy(['attendanceList' => $tid]) as $fillout) {
            $filloutList[$fillout->getParticipant()->getAid()] = $fillout;
        }

        $statusFormatter = ParticipantStatus::formatter();

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

        $repositoryParticipant = $this->getDoctrine()->getRepository(Participant::class);
        $participant           = $repositoryParticipant->findOneBy(['aid' => $aid]);
        $repositoryList        = $this->getDoctrine()->getRepository(AttendanceList::class);
        $list                  = $repositoryList->findOneBy(['tid' => $tid]);
        $event                 = $list->getEvent();
        $repositoryFillout     = $this->getDoctrine()->getRepository(AttendanceListFillout::class);

        $this->denyAccessUnlessGranted('participants_edit', $event);

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
