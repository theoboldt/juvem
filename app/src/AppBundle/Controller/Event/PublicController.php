<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event;


use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\FlashBagAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\ImageResponse;
use AppBundle\Manager\Calendar\CalendarManager;
use AppBundle\Manager\UploadImageManager;
use AppBundle\ResponseHelper;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;


class PublicController
{
    use WaitingListFlashTrait, RenderingControllerTrait, RoutingControllerTrait, DoctrineAwareControllerTrait, FlashBagAwareControllerTrait;
    
    /**
     * app.upload_image_manager
     *
     * @var UploadImageManager
     */
    private UploadImageManager $uploadImageManager;

    /**
     * @var CalendarManager 
     */
    private CalendarManager $calendarManager;

    /**
     * PublicController constructor.
     *
     * @param UploadImageManager $uploadImageManager
     * @param CalendarManager    $calendarManager
     * @param Environment        $twig
     * @param RouterInterface    $router
     * @param ManagerRegistry    $doctrine
     * @param SessionInterface   $session
     */
    public function __construct(
        UploadImageManager $uploadImageManager,
        CalendarManager    $calendarManager,
        Environment        $twig,
        RouterInterface    $router,
        ManagerRegistry    $doctrine,
        SessionInterface   $session

    ) {
        $this->uploadImageManager = $uploadImageManager;
        $this->calendarManager    = $calendarManager;
        $this->twig               = $twig;
        $this->router             = $router;
        $this->doctrine           = $doctrine;
        $this->session            = $session;
    }
    
    /**
     * Original image file for event image
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/image/original", name="event_image_original")
     */
    public function eventImageOriginalAction(Request $request, Event $event)
    {
        $image = $this->uploadImageManager->fetch($event->getImageFilename());

        return ImageResponse::createFromRequest($image, $request);
    }

    /**
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/image/{width}/{height}", requirements={"eid": "\d+", "width": "\d+", "height": "\d+"},
     *                                               name="event_image")
     */
    public function eventImageAction(Request $request, Event $event, $width, $height)
    {
        $image = $this->uploadImageManager->fetchResized($event->getImageFilename(), $width, $height);

        return ImageResponse::createFromRequest($image, $request);
    }

    /**
     * Page for details of an event
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}", requirements={"eid": "\d+"}, name="event_public_detail")
     */
    public function showAction(Event $event)
    {
        $publicCalendarUri = $this->calendarManager ? $this->calendarManager->getPublicCalendarUri() : null;
        $this->addWaitingListFlashIfRequired($event);
        return $this->render(
            'event/public/detail.html.twig',
            [
                'event'             => $event,
                'pageDescription'   => $event->getDescriptionMeta(true),
                'publicCalendarUri' => $publicCalendarUri,
            ]
        );
    }

    /**
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/juvem-event-{eid}.ics", requirements={"eid": "\d+"}, name="event_public_calendar")
     * @param Event $event
     * @return Response
     */
    public function provideCalendarEntryAction(Event $event) {
        $calendarEntry = null;

        if ($this->calendarManager->hasPublicCalendarUri()) {
            return new RedirectResponse(
                $this->calendarManager->getPublicCalendarUri(true)
                . '/'
                . CalendarManager::createJuvemEventName($event)
                . '.ics',
                Response::HTTP_MOVED_PERMANENTLY
            );
        } elseif ($this->calendarManager->hasConnector()) {
            $calendarEntry = $this->calendarManager->provideEventCalendarEntry($event);
        }

        if (!$calendarEntry) {
            throw new BadRequestHttpException(
                'Failed to fetch or generate calendar entry for event ' . $event->getEid()
            );
        }

        $response = new Response($calendarEntry);

        ResponseHelper::configureAttachment(
            $response,
            CalendarManager::createJuvemEventName($event) . '.ics',
            'text/calendar; charset=utf-8; component=vevent'
        );

        return $response;
    }

    /**
     * Redirect for routes
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/{eid}{wildcard}", requirements={"eid": "\d+", "wildcard": "(|\.|,|\s|\/)"})
     * @Route("/{eventIdentifier}/{eid}{wildcard}", requirements={"eventIdentifier": "(event|e)", "eid": "\d+", "wildcard": "(\.|,|\s|\/)"})
     */
    public function redirectToShowAction(Event $event)
    {
        return $this->redirectToRoute(
            'event_public_detail', array('eid' => $event->getEid()), Response::HTTP_MOVED_PERMANENTLY
        );
    }

    /**
     * Short url redirecting to details of an event
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/e/{eid}", requirements={"eid": "\d+"}, name="event_public_short")
     */
    public function shortLinkAction(Event $event)
    {
        return $this->redirectToRoute(
            'event_public_detail', array('eid' => $event->getEid()), Response::HTTP_MOVED_PERMANENTLY
        );
    }

    /**
     * Active and visible events as list group
     *
     * @return Response
     */
    public function listActiveEventsAction()
    {
        $repository = $this->getDoctrine()->getRepository(Event::class);
        $eventList  = $repository->findAllWithCounts();

        return $this->render(
            'event/public/embed-list-group.html.twig',
            array(
                'events' => $eventList
            )
        );

    }

    /**
     * Active and visible events as list group
     *
     * @return Response
     */
    public function listActiveEventLinksAction()
    {
        $repository = $this->getDoctrine()->getRepository(Event::class);
        $eventList  = $repository->findAllWithCounts();

        return $this->render(
            'event/public/embed-link-list.html.twig',
            array(
                'events' => $eventList
            )
        );

    }
}
