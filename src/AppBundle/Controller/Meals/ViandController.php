<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Meals;

use AppBundle\Entity\Meals\Viand;
use AppBundle\Entity\User;
use AppBundle\Form\Meal\ViandType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ViandController extends AbstractController
{
    /**
     * @Route("/admin/meals/viands/list", name="meals_viands_list")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function listAction()
    {
        return $this->render('meals/viand/list.html.twig');
    }
    
    /**
     * Data provider for event list grid
     *
     * @Route("/admin/meals/viands/list.json", name="meals_viands_list_data")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function listDataAction()
    {
        $repository = $this->getDoctrine()->getRepository(Viand::class);
        $viandList  = $repository->findAll();
        
        $result = [];
        /** @var Viand $viand */
        foreach ($viandList as $viand) {
            $properties = [];
            foreach ($viand->getProperties() as $property) {
                $properties[] = '<span class="label label-primary">' . $property->getName() . '</span>';
            }
            
            $result[] = [
                'id'           => $viand->getId(),
                'name'         => $viand->getName(),
                'default_unit' => $viand->hasDefaultUnit() ? $viand->getDefaultUnit()->getName() : null,
                'properties'   => implode(' ', $properties),
            ];
        }
        
        
        return new JsonResponse($result);
    }
    
    /**
     * @ParamConverter("viand", class="AppBundle\Entity\Meals\Viand")
     * @Route("/admin/meals/viands/{id}", requirements={"id": "\d+"}, name="meals_viands_detail")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function detailAction(Viand $viand)
    {
        return $this->render('meals/viand/detail.html.twig', ['viand' => $viand]);
    }
    
    /**
     * @ParamConverter("viand", class="AppBundle\Entity\Meals\Viand")
     * @Route("/admin/meals/viands/{id}/edit", requirements={"id": "\d+"}, name="meals_viands_edit")
     * @Security("is_granted('ROLE_ADMIN')")
     * @param Request $request
     * @param Viand $viand
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Viand $viand)
    {
        $form = $this->createForm(ViandType::class, $viand);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ($this->getUser() instanceof User) {
                $viand->setModifiedBy($this->getUser());
            }
            
            $em->persist($viand);
            $em->flush();
            
            return $this->redirectToRoute('meals_viands_detail', ['id' => $viand->getId()]);
        }
        
        return $this->render(
            'meals/viand/edit.html.twig',
            [
                'viand' => $viand,
                'form'  => $form->createView(),
            ]
        );
    }
    
    /**
     * Create a new acquisition attribute
     *
     * @Route("/admin/meals/viands/new", name="meals_viands_new")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $viand = new Viand();
        $form  = $this->createForm(ViandType::class, $viand);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            if ($this->getUser() instanceof User) {
                $viand->setCreatedBy($this->getUser());
            }
            $em->persist($viand);
            $em->flush();
            
            return $this->redirectToRoute('meals_viands_list');
        }
        
        return $this->render(
            'meals/viand/new.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}