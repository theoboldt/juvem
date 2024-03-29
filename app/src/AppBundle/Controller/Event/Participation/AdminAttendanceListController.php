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

use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AttendanceList\AttendanceList;
use AppBundle\Entity\AttendanceList\AttendanceListColumn;
use AppBundle\Entity\AttendanceList\AttendanceListFilloutParticipantRepository;
use AppBundle\Entity\AttendanceList\AttendanceListParticipantFillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationRepository;
use AppBundle\Entity\User;
use AppBundle\Export\AttendanceListExport;
use AppBundle\Form\AttendanceListType;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\InvalidTokenHttpException;
use AppBundle\ResponseHelper;
use AppBundle\Twig\GlobalCustomization;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

class AdminAttendanceListController
{
    use RenderingControllerTrait, DoctrineAwareControllerTrait, FormAwareControllerTrait, RoutingControllerTrait, AuthorizationAwareControllerTrait;

    /**
     * security.csrf.token_manager
     *
     * @var CsrfTokenManagerInterface
     */
    private CsrfTokenManagerInterface $csrfTokenManager;

    /**
     * @var GlobalCustomization
     */
    private GlobalCustomization $globalCustomization;

    /**
     * AdminController constructor.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param ManagerRegistry $doctrine
     * @param RouterInterface $router
     * @param Environment $twig
     * @param FormFactoryInterface $formFactory
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param GlobalCustomization $globalCustomization
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        RouterInterface $router,
        Environment $twig,
        FormFactoryInterface $formFactory,
        CsrfTokenManagerInterface $csrfTokenManager,
        GlobalCustomization $globalCustomization
    )
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
        $this->doctrine             = $doctrine;
        $this->router               = $router;
        $this->twig                 = $twig;
        $this->formFactory          = $formFactory;
        $this->csrfTokenManager     = $csrfTokenManager;
        $this->globalCustomization  = $globalCustomization;
    }

    /**
     * @CloseSessionEarly
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
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/attendance-lists.json", requirements={"eid": "\d+"},
     *                                                    name="event_attendance_lists_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function listAttendanceListsDataAction(Event $event)
    {
        $repository = $this->getDoctrine()->getRepository(AttendanceList::class);
        $eid        = $event->getEid();

        $result = $repository->findBy(['event' => $eid], ['startDate' => 'ASC', 'title' => 'ASC']);

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
                            ? $list->getStartDate()->format(Event::DATE_FORMAT_DATE)
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
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/attendance/new", requirements={"eid": "\d+"}, name="event_attendance_list_new")
     * @Security("is_granted('participants_read', event)")
     */
    public function newAction(Event $event, Request $request)
    {
        $list = new AttendanceList($event);
        $form = $this->createForm(AttendanceListType::class, $list);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($list);
            $em->flush();

            return $this->redirectToRoute('event_attendance_details', ['eid' => $event->getEid(), 'tid' => $list->getTid()]);
        }

        return $this->render('/event/attendance/new.html.twig', ['form' => $form->createView(), 'event' => $event]);
    }

    /**
     * Create a new attendance list
     *
     * @CloseSessionEarly
     * @ParamConverter("previous", class="AppBundle\Entity\AttendanceList\AttendanceList", options={"id" = "tid"})
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/attendance/{tid}/followup", requirements={"eid": "\d+", "tid": "\d+"},
     *                                                    name="event_attendance_followup")
     * @Security("is_granted('participants_read', event)")
     */
    public function createFollowupAction(Event $event, AttendanceList $previous, Request $request)
    {
        $list = new AttendanceList($event);
        $list->setEvent($event);
        if (preg_match('/^(?P<title>.*)(\s*)(\d+)\.(\d+)\.(\d+)(\s*)$/', $previous->getTitle(), $matches)) {
            $title = trim($matches['title']);
        } else {
            $title = $previous->getTitle();
        }
        if ($previous->getStartDate()) {
            $date = clone $previous->getStartDate();
            $date->modify('+1 day');
            $list->setStartDate($date);

            $title .= ' ' . $date->format(\AppBundle\Entity\Event::DATE_FORMAT_DATE);
        }
        $list->setTitle($title);

        foreach ($previous->getColumns() as $column) {
            $list->addColumn($column);
        }

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
     * @CloseSessionEarly
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

            return $this->redirectToRoute('event_attendance_details', ['eid' => $event->getEid(), 'tid' => $list->getTid()]);
        }

        return $this->render(
            '/event/attendance/edit.html.twig', ['form' => $form->createView(), 'list' => $list, 'event' => $event]
        );
    }

    /**
     * View an attendance list
     *
     * @CloseSessionEarly
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
     * @CloseSessionEarly
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
     * @CloseSessionEarly
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
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('attendance' . $list->getTid())) {
            throw new InvalidTokenHttpException();
        }

        if ($list->getEvent()->getEid() !== $event->getEid()) {
            throw new BadRequestHttpException('List and event are incompatible');
        }

        $updates = [];
        foreach ($request->get('updates') as $columnId => $choices) {
            foreach ($choices as $choiceId => $aids) {
                foreach ($aids as $aid) {
                    $updates[] = [
                        'aid'      => (int)$aid,
                        'columnId' => (int)$columnId,
                        'choiceId' => $choiceId === 0 ? null : (int)$choiceId
                    ];
                }
            }
        }

        $repositoryFillout = $this->getDoctrine()->getRepository(AttendanceListParticipantFillout::class);

        $repositoryFillout->processUpdates($list, $updates);

        return $this->provideAttendanceListData($event, $list);
    }

    /**
     * @CloseSessionEarly
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
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('attendance-comment' . $list->getTid())) {
            throw new InvalidTokenHttpException();
        }
        $repositoryFillout     = $this->getDoctrine()->getRepository(AttendanceListParticipantFillout::class);
        $repositoryParticipant = $this->getDoctrine()->getRepository(Participant::class);
        /** @var Participant $participant */
        $participant = $repositoryParticipant->find($request->get('aid'));
        /** @var AttendanceListColumn $column */
        $column  = $repositoryFillout->findColumnById($request->get('columnId'));
        $fillout = $repositoryFillout->findFillout(
            $participant,
            $list,
            $column
        );
        $comment = trim($request->get('comment'));
        if ($fillout) {
            $fillout->setComment(empty($comment) ? null : $comment);
        } elseif (!empty($comment)) {
            $fillout = new AttendanceListParticipantFillout(
                $list,
                $participant,
                $column,
                null,
                $comment
            );
        }
        $em = $this->getDoctrine()->getManager();
        if (empty($comment) && !$fillout->getChoice()) {
            $em->remove($fillout);
        } else {
            $em->persist($fillout);
        }
        $em->flush();
        return new JsonResponse([]);
    }

    /**
     * @CloseSessionEarly
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
        return $this->createExportResponse([$list], $attribute);
    }

    /**
     * @CloseSessionEarly
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

    /**
     * Create export of multiple lists
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @Route("/admin/event/{eid}/attendance/export-multiple/0/{listids}", requirements={"eid": "\d+", "listids": "[\d,]+"})
     * @Route("/admin/event/{eid}/attendance/export-multiple/{bid}/{listids}", requirements={"eid": "\d+", "bid":"\d+", "listids": "[\d,]+"})
     * @Security("is_granted('participants_read', event)")
     * @param Event $event
     * @param Attribute $attribute
     * @param Request $request
     * @return Response
     */
    public function exportMultipleAttendanceListsAction(Request $request, Event $event, ?Attribute $attribute = null): Response
    {
        $listIds = array_map(
            function ($listId) {
                return (int)$listId;
            }, explode(',', $request->get('listids'))
        );

        /** @var AttendanceListFilloutParticipantRepository $filloutRepository */
        $filloutRepository = $this->getDoctrine()->getRepository(AttendanceListParticipantFillout::class);
        $lists             = $filloutRepository->findByIds($listIds);

        return $this->createExportResponse($lists, $attribute);
    }

    /**
     * Create a attendance list export
     *
     * @param array|AttendanceList[] $lists Lists to include in export
     * @param Attribute|null $attribute
     * @return Response
     */
    private function createExportResponse(array $lists, ?Attribute $attribute = null): Response
    {
        if (!count($lists)) {
            throw new BadRequestHttpException('No lists configured for export');
        }
        /** @var AttendanceList $list */
        $list  = reset($lists);
        $event = $list->getEvent();

        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participantList         = $participationRepository->participantsList($event, null, false, false);

        $filloutRepository = $this->getDoctrine()->getRepository(AttendanceListParticipantFillout::class);
        $attendanceData    = [];
        foreach ($lists as $list) {
            if ($list->getEvent()->getEid() !== $event->getEid()) {
                throw new BadRequestHttpException('Export of lists of different events requested');
            }
            $attendanceData[$list->getTid()] = $filloutRepository->fetchAttendanceListDataForList($list);
        }

        if ($attribute) {
            $participantList = ParticipationRepository::sortAndGroupParticipantList(
                $participantList, null, $attribute->getCustomFieldName()
            );
        }

        $user = $this->getUser();
        $export = new AttendanceListExport(
            $this->globalCustomization, $lists, $participantList, $attendanceData, ($user instanceof User) ? $user : null,
            $attribute
        );
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        ResponseHelper::configureAttachment(
            $response,
            $list->getTitle() . ' - Anwesenheitsliste.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        return $response;
    }
}
