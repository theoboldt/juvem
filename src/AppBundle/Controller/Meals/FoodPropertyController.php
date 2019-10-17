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

use AppBundle\Entity\Meals\FoodProperty;
use AppBundle\Entity\Meals\Viand;
use AppBundle\Entity\User;
use AppBundle\Form\Meal\FoodPropertyType;
use AppBundle\Form\Meal\ViandType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FoodPropertyController extends Controller
{
    /**
     * @Route("/admin/meals/properties/list", name="meals_properties_list")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listAction()
    {
        return $this->render('meals/food_property/list.html.twig');
    }
    
    /**
     * Data provider for event list grid
     *
     * @Route("/admin/meals/properties/list.json", name="meals_properties_list_data")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listDataAction()
    {
        $repository = $this->getDoctrine()->getRepository(FoodProperty::class);
        $properties = $repository->findAll();
        
        $result = [];
        /** @var FoodProperty $property */
        foreach ($properties as $property) {
            $result[] = [
                'id'                         => $property->getId(),
                'name'                       => $property->getName(),
                'exclusion_term'             => $property->getExclusionTerm(),
                'exclusion_term_description' => $property->getExclusionTermDescription(),
                'exclusion_term_short'       => $property->getExclusionTermShort(),
            ];
        }
        
        
        return new JsonResponse($result);
    }
    
    /**
     * @ParamConverter("foodProperty", class="AppBundle\Entity\Meals\FoodProperty")
     * @Route("/admin/meals/properties/{id}", requirements={"id": "\d+"}, name="meals_property_detail")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function detailAction(FoodProperty $foodProperty)
    {
        return $this->render('meals/food_property/detail.html.twig', ['foodProperty' => $foodProperty]);
    }
    
    /**
     * @ParamConverter("foodProperty", class="AppBundle\Entity\Meals\FoodProperty")
     * @Route("/admin/meals/properties/{id}/edit", requirements={"id": "\d+"}, name="meals_property_edit")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editAction(Request $request, FoodProperty $foodProperty)
    {
        $form = $this->createForm(FoodPropertyType::class, $foodProperty);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ($this->getUser() instanceof User) {
                $foodProperty->setModifiedBy($this->getUser());
            }
            $em->persist($foodProperty);
            $em->flush();
            
            return $this->redirectToRoute('meals_property_detail', ['id' => $foodProperty->getId()]);
        }
        
        return $this->render(
            'meals/food_property/edit.html.twig',
            [
                'foodProperty' => $foodProperty,
                'form'         => $form->createView(),
            ]
        );
    }
    
    /**
     * Create a new acquisition attribute
     *
     * @Route("/admin/meals/properties/new", name="meals_property_new")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $property = new FoodProperty('');
        $form     = $this->createForm(FoodPropertyType::class, $property);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            if ($this->getUser() instanceof User) {
                $property->setCreatedBy($this->getUser());
            }
            $em->persist($property);
            $em->flush();
            
            return $this->redirectToRoute('meals_properties_list');
        }
        
        return $this->render(
            'meals/food_property/new.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}