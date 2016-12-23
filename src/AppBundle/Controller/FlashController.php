<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Flash;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FlashController extends Controller
{
    /**
     * @Route("/admin/flash/list", name="flash_list")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listAction(Request $request)
    {
        return $this->render('flash/list.html.twig');
    }

    /**
     * Data provider for event list grid
     *
     * @Route("/admin/flash/list.json", name="flash_list_data")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listDataAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Flash');
        $list       = $repository->findValid();

        $result = array();
        /** @var Flash $flash */
        foreach ($list as $flash) {

            $flashValidFrom = $flash->getValidFrom();
            if ($flashValidFrom) {
                $flashValidFrom = $flashValidFrom->format(Event::DATE_FORMAT_DATE_TIME);
            }

            $flashValidUntil = $flash->getValidUntil();
            if ($flashValidUntil) {
                $flashValidUntil = $flashValidUntil->format(Event::DATE_FORMAT_DATE_TIME);
            }

            $result[] = array(
                'fid'        => $flash->getFid(),
                'validFrom'  => $flashValidFrom,
                'validUntil' => $flashValidUntil
            );
        }

        return new JsonResponse($result);
    }
}