<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
        $eventList = $repository->findBy(
            array('isVisible' => true, 'isActive' => true),
            array('startDate' => 'ASC', 'startTime' => 'ASC')
        );

        return $this->render('default/index.html.twig', array(
            'events' => $eventList
        ));
    }
}
