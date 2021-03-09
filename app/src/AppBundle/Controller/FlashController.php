<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Flash;
use AppBundle\Form\FlashType;
use AppBundle\Http\Annotation\CloseSessionEarly;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FlashController extends AbstractController
{
    /**
     * @CloseSessionEarly
     * @Route("/admin/flash/list", name="flash_list")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function listAction()
    {
        return $this->render('flash/list.html.twig');
    }

    /**
     * Data provider for event list grid
     *
     * @CloseSessionEarly
     * @Route("/admin/flash/list.json", name="flash_list_data")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function listDataAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository(Flash::class);
        $list       = $repository->findAll();

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
     * @CloseSessionEarly
     * @Route("/admin/flash/new", name="flash_new")
     * @Security("is_granted('ROLE_ADMIN')")
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
     * @CloseSessionEarly
     * @Route("/admin/flash/{fid}", requirements={"fid": "\d+"}, name="flash_edit")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function editAction($fid, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository(Flash::class);
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