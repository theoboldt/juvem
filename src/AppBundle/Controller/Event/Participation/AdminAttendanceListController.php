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

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AttendanceList\AttendanceList;
use AppBundle\Entity\AttendanceList\AttendanceListParticipantFillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationRepository;
use AppBundle\Export\AttendanceListExport;
use AppBundle\Export\ParticipantsMailExport;
use AppBundle\Form\AttendanceListType;
use AppBundle\InvalidTokenHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
                    $columns = [];
                    foreach ($list->getColumns() as $column) {
                        $columns[] = $column->getTitle();
                    }
                    sort($columns);
                    
                    return [
                        'tid'        => $list->getTid(),
                        'eid'        => $eid,
                        'title'      => $list->getTitle(),
                        'startDate'  => $list->getStartDate()
                            ? $list->getStartDate()->format(Event::DATE_FORMAT_DATE_TIME)
                            : null,
                        'createdAt'  => $list->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME),
                        'modifiedAt' => $modifiedAt,
                        'columns'    => implode(', ', $columns),
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
     * View an attendance list
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("list", class="AppBundle\Entity\AttendanceList\AttendanceList", options={"id" = "tid"})
     * @Route("/admin/event/{eid}/attendance/{tid}", requirements={"eid": "\d+", "tid": "\d+"},
     *                                                    name="event_attendance_details")
     * @Security("is_granted('participants_read', event)")
     */
    public function detailAction(Event $event, AttendanceList $list, Request $request): Response
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participantEntityList   = $participationRepository->participantsList($event, null, false, false);
        
        return $this->render(
            '/event/attendance/detail.html.twig',
            ['list' => $list, 'event' => $event, 'participants' => $participantEntityList]
        );
    }
    
    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("list", class="AppBundle\Entity\AttendanceList\AttendanceList", options={"id" = "tid"})
     * @Route("/admin/event/{eid}/attendance/{tid}/fillout.json", requirements={"eid": "\d+", "tid": "\d+"}, methods={"GET"}, name="event_attendance_fillout_data")
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @param AttendanceList $list
     * @return Response
     */
    public function provideAttendanceListData(Event $event, AttendanceList $list): Response
    {
        $filloutRepository = $this->getDoctrine()->getRepository(AttendanceListParticipantFillout::class);
        $result            = $filloutRepository->fetchAttendanceListDataForList($list);
        
        $resultEncoded = json_encode(['participants' => $result]);
        $checksum      = sha1($resultEncoded);
        return new JsonResponse($resultEncoded, Response::HTTP_OK, ['X-Response-Checksum' => $checksum], true);
    }
    
    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("list", class="AppBundle\Entity\AttendanceList\AttendanceList", options={"id" = "tid"})
     * @Route("/admin/event/{eid}/attendance/{tid}/fillout.json", requirements={"eid": "\d+", "tid": "\d+"}, methods={"POST"}, name="event_attendance_fillout_update")
     * @Security("is_granted('participants_edit', event)")
     * @param Event $event
     * @param AttendanceList $list
     * @param Request $request
     * @return Response
     */
    public function updateAttendanceListFillouts(Event $event, AttendanceList $list, Request $request): Response
    {
        $token = $request->get('_token');
        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('attendance' . $list->getTid())) {
            throw new InvalidTokenHttpException();
        }
        
        if ($list->getEvent()->getEid() !== $event->getEid()) {
            throw new BadRequestHttpException('List and event are incompatible');
        }
        
        $updates           = array_map(
            function ($update) {
                return [
                    'aid'      => (int)$update['aid'],
                    'columnId' => (int)$update['columnId'],
                    'choiceId' => $update['choiceId'] === 0 ? null : (int)$update['choiceId']
                ];
            }, $request->get('updates')
        );
        $repositoryFillout = $this->getDoctrine()->getRepository(AttendanceListParticipantFillout::class);
        
        $repositoryFillout->processUpdates($list, $updates);
        
        return $this->provideAttendanceListData($event, $list);
    }
    
    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("list", class="AppBundle\Entity\AttendanceList\AttendanceList", options={"id" = "tid"})
     * @Route("/admin/event/{eid}/attendance/{tid}/comment.json", requirements={"eid": "\d+", "tid": "\d+"}, methods={"POST"}, name="event_attendance_fillout_comment")
     * @Security("is_granted('participants_edit', event)")
     * @param Event $event
     * @param AttendanceList $list
     * @param Request $request
     * @return Response
     */
    public function updateAttendanceListComment(Event $event, AttendanceList $list, Request $request): Response
    {
        $token = $request->get('_token');
        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('attendance-comment' . $list->getTid())) {
            throw new InvalidTokenHttpException();
        }
        $repositoryFillout     = $this->getDoctrine()->getRepository(AttendanceListParticipantFillout::class);
        $repositoryParticipant = $this->getDoctrine()->getRepository(Participant::class);
        $fillout               = $repositoryFillout->findFillout(
            $repositoryParticipant->find($request->get('aid')),
            $list,
            $repositoryFillout->findColumnById($request->get('columnId'))
        );
        if ($fillout) {
            $comment = trim($request->get('comment'));
            $fillout->setComment(empty($comment) ? null : $comment);
            $em = $this->getDoctrine()->getManager();
            $em->persist($fillout);
            $em->flush();
            return new JsonResponse([]);
        } else {
            return new JsonResponse(
                ['message' => 'Wenn nichts ausgewählt ist, kann kein Kommentar gespeichert werden']
            );
        }
    }
    
    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @ParamConverter("list", class="AppBundle\Entity\AttendanceList\AttendanceList", options={"id" = "tid"})
     * @Route("/admin/event/{eid}/attendance/{tid}/export/{bid}", requirements={"eid": "\d+", "tid": "\d+", "bid": "\d+"}, name="event_attendance_fillout_export_grouped")
     * @Security("is_granted('participants_read', event)")
     * @param AttendanceList $list
     * @param Attribute $attribute
     * @return Response
     */
    public function exportAttendanceListGroupedAction(AttendanceList $list, ?Attribute $attribute = null): Response
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participantList         = $participationRepository->participantsList($list->getEvent(), null, false, false);
        
        $filloutRepository = $this->getDoctrine()->getRepository(AttendanceListParticipantFillout::class);
        $attendanceData    = $filloutRepository->fetchAttendanceListDataForList($list);
    
        if ($attribute) {
            $participantList = ParticipationRepository::sortAndGroupParticipantList(
                $participantList, null, $attribute->getName()
            );
        }
        
        $export = new AttendanceListExport(
            $this->get('app.twig_global_customization'), $list, $participantList, $attendanceData, $this->getUser(), $attribute
        );
        $export->setMetadata();
        $export->process();
        
        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $list->getTitle() . ' - Anwesenheitsliste.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);
        
        return $response;
    }
    
    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("list", class="AppBundle\Entity\AttendanceList\AttendanceList", options={"id" = "tid"})
     * @Route("/admin/event/{eid}/attendance/{tid}/export", requirements={"eid": "\d+", "tid": "\d+"}, name="event_attendance_fillout_export")
     * @Security("is_granted('participants_read', event)")
     * @param AttendanceList $list
     * @return Response
     */
    public function exportAttendanceListAction(AttendanceList $list): Response
    {
        return $this->exportAttendanceListGroupedAction($list, null);
    }
}
