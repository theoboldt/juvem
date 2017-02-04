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


use AppBundle\ImageResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class PublicController extends Controller
{
    /**
     * Detail page for one single event
     *
     * @Route("/event/{eid}/image/{width}/{height}", requirements={"eid": "\d+", "width": "\d+", "height": "\d+"},
     *                                               name="event_image")
     */
    public function eventImageAction($eid, $width, $height)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $uploadManager = $this->get('app.upload_image_manager');
        $image         = $uploadManager->fetchResized($event->getImageFilename(), $width, $height);

        return new ImageResponse($image);
    }


    /**
     * Page for details of an event
     *
     * @Route("/event/{eid}", requirements={"eid": "\d+"}, name="event_public_detail")
     */
    public function showAction($eid)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );

        }

        return $this->render(
            'event/public/detail.html.twig', array(
                                               'event' => $event
                                           )
        );
    }

    /**
     * Short url redirecting to details of an event
     *
     * @Route("/e/{eid}", requirements={"eid": "\d+"}, name="event_public_short")
     */
    public function shortLinkAction($eid)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $event      = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }
        if ($event->isVisible()) {
            return $this->redirectToRoute(
                'event_public_detail', array('eid' => $eid), Response::HTTP_MOVED_PERMANENTLY
            );
        } else {
            return $this->render(
                'event/public/miss-invisible.html.twig', array('eid' => $eid)
            );
        }
    }

    /**
     * Active and visible events as list group
     *
     * @return Response
     */
    public function listActiveEventsAction()
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Event');
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
        $repository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $eventList  = $repository->findAllWithCounts();

        return $this->render(
            'event/public/embed-link-list.html.twig',
            array(
                'events' => $eventList
            )
        );

    }
}