<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
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

        if (new  \DateTime() < new \DateTime('2016-11-14 08:23:26.000000')) {
            $this->addFlash(
                'info',
                'Bis zum 10.11.2016 kam es vereinzelt zu Schwierigkeiten beim Anmeldevorgang. Diese Probleme sind jetzt behoben. Sollten dennoch etwas nicht funktioneren, könenn Sie sich auch gerne an <a href="mailto:jungschar.vaihingen@gmail.com">jungschar.vaihingen@gmail.com</a> wenden.'
            );
        }

        return $this->render(
            'default/index.html.twig', array(
                                         'events' => $eventList
                                     )
        );
    }

    /**
     * @Route("/legal", name="legal")
     * @Route("/datenschutzerklaerung")
     * @Route("/datenschutz")
     */
    public function legalAction(Request $request)
    {
        return $this->render(
            'legal/privacy-page.html.twig'
        );
    }


    /**
     * @Route("/impressum", name="impressum")
     */
    public function impressumAction(Request $request)
    {
        return $this->render(
            'legal/impressum-page.html.twig'
        );
    }

    /**
     * @Route("/css/all.css.map")
     * @Route("/js/all.js.map")
     * @Route("/css/all.min.css.map")
     * @Route("/js/all.min.js.map")
     */
    public function ressourceUnavailableAction(Request $request)
    {
        return new Response(null, Response::HTTP_GONE);
    }
}
