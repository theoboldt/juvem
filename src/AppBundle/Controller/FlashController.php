<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Flash;
use AppBundle\Form\FlashType;
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
    public function listAction()
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

        $result = [];
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

            $result[] = [
                'fid'         => $flash->getFid(),
                'message'     => $flash->getMessage(),
                'valid_from'  => $flashValidFrom,
                'valid_until' => $flashValidUntil
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * Create a new flash message
     *
     * @Route("/admin/flash/new", name="flash_new")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $flash = new Flash();

        $form = $this->createForm(FlashType::class, $flash);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($flash);
            $em->flush();

            return $this->redirectToRoute('flash_list');
        }

        return $this->render('flash/new.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edit flash message
     *
     * @Route("/admin/flash/{fid}", requirements={"fid": "\d+"}, name="flash_edit")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editAction($fid, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Flash');
        $flash      = $repository->findOneBy(['fid' => $fid]);

        $form = $this->createForm(FlashType::class, $flash);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($flash);
            $em->flush();

            return $this->redirectToRoute('flash_list');
        }

        return $this->render('flash/edit.html.twig', ['form' => $form->createView()]);
    }
}