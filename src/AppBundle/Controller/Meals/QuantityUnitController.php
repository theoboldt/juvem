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

use AppBundle\Entity\Meals\QuantityUnit;
use AppBundle\Entity\User;
use AppBundle\Form\Meal\QuantityUnitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class QuantityUnitController extends AbstractController
{
    /**
     * @Route("/admin/meals/units/list", name="meals_units_list")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listAction()
    {
        return $this->render('meals/unit/list.html.twig');
    }
    
    /**
     * Data provider for event list grid
     *
     * @Route("/admin/meals/units/list.json", name="meals_units_list_data")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listDataAction()
    {
        $repository = $this->getDoctrine()->getRepository(QuantityUnit::class);
        $unitList   = $repository->findAll();
        
        $result = [];
        /** @var QuantityUnit $unit */
        foreach ($unitList as $unit) {
            
            $result[] = [
                'id'    => $unit->getId(),
                'name'  => $unit->getName(),
                'short' => $unit->getShort(),
            ];
        }
        
        
        return new JsonResponse($result);
    }
    
    /**
     * @ParamConverter("unit", class="AppBundle\Entity\Meals\QuantityUnit")
     * @Route("/admin/meals/units/{id}", requirements={"id": "\d+"}, name="meals_units_detail")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function detailAction(QuantityUnit $unit)
    {
        return $this->render('meals/unit/detail.html.twig', ['unit' => $unit]);
    }
    
    /**
     * @ParamConverter("unit", class="AppBundle\Entity\Meals\QuantityUnit")
     * @Route("/admin/meals/units/{id}/edit", requirements={"id": "\d+"}, name="meals_units_edit")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editAction(Request $request, QuantityUnit $unit)
    {
        $form = $this->createForm(QuantityUnitType::class, $unit);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ($this->getUser() instanceof User) {
                $unit->setModifiedBy($this->getUser());
            }
            
            $em->persist($unit);
            $em->flush();
            
            return $this->redirectToRoute('meals_units_detail', ['id' => $unit->getId()]);
        }
        
        return $this->render(
            'meals/unit/edit.html.twig',
            [
                'unit' => $unit,
                'form' => $form->createView(),
            ]
        );
    }
    
    /**
     * Create a new acquisition attribute
     *
     * @Route("/admin/meals/units/new", name="meals_units_new")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $unit = new QuantityUnit();
        $form = $this->createForm(QuantityUnitType::class, $unit);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            if ($this->getUser() instanceof User) {
                $unit->setCreatedBy($this->getUser());
            }
            $em->persist($unit);
            $em->flush();
            
            return $this->redirectToRoute('meals_units_list');
        }
        
        return $this->render(
            'meals/unit/new.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}