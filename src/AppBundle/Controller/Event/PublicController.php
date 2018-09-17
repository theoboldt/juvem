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


use AppBundle\Entity\Event;
use AppBundle\ImageResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class PublicController extends Controller
{
    use WaitingListFlashTrait;

    /**
     * Original image file for event image
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/image/original", name="event_image_original")
     */
    public function eventImageOriginalAction(Request $request, Event $event)
    {
        $uploadManager = $this->get('app.upload_image_manager');
        $image         = $uploadManager->fetch($event->getImageFilename());

        return ImageResponse::createFromRequest($image, $request);
    }

    /**
     *
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/image/{width}/{height}", requirements={"eid": "\d+", "width": "\d+", "height": "\d+"},
     *                                               name="event_image")
     */
    public function eventImageAction(Request $request, Event $event, $width, $height)
    {
        $uploadManager = $this->get('app.upload_image_manager');
        $image         = $uploadManager->fetchResized($event->getImageFilename(), $width, $height);

        return ImageResponse::createFromRequest($image, $request);
    }

    /**
     * Page for details of an event
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}", requirements={"eid": "\d+"}, name="event_public_detail")
     */
    public function showAction(Event $event)
    {
        $this->addWaitingListFlashIfRequired($event);
        return $this->render(
            'event/public/detail.html.twig',
            ['event' => $event, 'pageDescription' => $event->getDescriptionMeta(true)]
        );
    }

    /**
     * Redirect for routes
     *
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