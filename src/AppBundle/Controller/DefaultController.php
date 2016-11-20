<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');
        $eventList  = $repository->findBy(
            array('isVisible' => true
            ),
            array('startDate' => 'ASC',
                  'startTime' => 'ASC'
            )
        );

        $user           = $this->getUser();
        $participations = array();
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        return $this->render(
            'default/index.html.twig',
            array(
                'events'         => $eventList,
                'participations' => $participations
            )
        );
    }

    /**
     * @Route("/legal", name="legal")
     * @Route("/datenschutzerklaerung")
     * @Route("/datenschutz")
     */
    public function legalAction()
    {
        return $this->render(
            'legal/privacy-page.html.twig'
        );
    }


    /**
     * @Route("/impressum", name="impressum")
     */
    public function impressumAction()
    {
        return $this->render(
            'legal/impressum-page.html.twig'
        );
    }

    /**
     * @Route("/heartbeat", name="heartbeat")
     */
    public function heartbeatAction()
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/css/all.css.map")
     * @Route("/js/all.js.map")
     * @Route("/css/all.min.css.map")
     * @Route("/js/all.min.js.map")
     */
    public function ressourceUnavailableAction()
    {
        return new Response(null, Response::HTTP_GONE);
    }
}
