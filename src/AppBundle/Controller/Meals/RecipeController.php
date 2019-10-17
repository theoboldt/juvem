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

use AppBundle\Entity\Event;
use AppBundle\Entity\Meals\Recipe;
use AppBundle\Entity\Meals\RecipeFeedback;
use AppBundle\Entity\User;
use AppBundle\Form\MealFeedbackType;
use AppBundle\Form\RecipeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RecipeController extends Controller
{
    /**
     * @Route("/admin/meals/recipes")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listRedirectAction()
    {
        return $this->redirectToRoute('meals_recipes_list');
    }
    
    /**
     * @Route("/admin/meals/recipes/list", name="meals_recipes_list")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listAction()
    {
        return $this->render('meals/recipe/list.html.twig');
    }
    
    /**
     * Data provider for event list grid
     *
     * @Route("/admin/meals/recipes/list.json", name="meals_recipes_list_data")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listDataAction()
    {
        $repository = $this->getDoctrine()->getRepository(Recipe::class);
        $recipeList = $repository->findAll();
        $result     = [];
        /** @var Recipe $recipe */
        foreach ($recipeList as $recipe) {
            $properties = [];
            foreach ($recipe->getProperties() as $property) {
                $properties[] = '<span class="label label-primary">' . $property->getName() . '</span>';
            }
            $notAssigned = $this->get('app.food_service')->findAllFoodPropertiesNotAssigned($recipe);
            foreach ($notAssigned as $property) {
                $properties[] = '<span class="label label-default">' . $property->getExclusionTerm() . '</span>';
            }
            
            $description = (mb_strlen($recipe->getCookingInstructions()) < 100)
                ? $recipe->getCookingInstructions()
                : substr($recipe->getCookingInstructions(), 0, 97) . '...';
            
            $result[] = [
                'id'          => $recipe->getId(),
                'title'       => $recipe->getTitle(),
                'description' => $description,
                'properties'  => implode(' ', $properties),
            ];
        }
        
        
        return new JsonResponse($result);
    }
    
    /**
     * @ParamConverter("recipe", class="AppBundle\Entity\Meals\Recipe")
     * @Route("/admin/meals/recipes/{id}", requirements={"id": "\d+"}, name="meals_recipes_detail")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function detailAction(Recipe $recipe)
    {
        return $this->render(
            'meals/recipe/detail.html.twig',
            [
                'recipe'                 => $recipe,
                'unassignedProperties'   => $this->get('app.food_service')->findAllFoodPropertiesNotAssigned($recipe),
                'accumulatedIngredients' => $this->get('app.food_service')->accumulatedIngredients($recipe),
            ]
        );
    }
    
    /**
     * @ParamConverter("recipe", class="AppBundle\Entity\Meals\Recipe")
     * @Route("/admin/meals/recipes/{id}/edit", requirements={"id": "\d+"}, name="meals_recipes_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param Recipe $recipe
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Recipe $recipe)
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ($this->getUser() instanceof User) {
                $recipe->setModifiedBy($this->getUser());
            }
            $em->persist($recipe);
            $em->flush();
            
            return $this->redirectToRoute('meals_recipes_detail', ['id' => $recipe->getId()]);
        }
        
        return $this->render(
            'meals/recipe/edit.html.twig',
            [
                'recipe' => $recipe,
                'form'   => $form->createView(),
            ]
        );
    }
    
    /**
     * Collect new recipe feedback
     *
     * @ParamConverter("recipe", class="AppBundle\Entity\Meals\Recipe")
     * @Route("/admin/meals/recipes/{id}/feedback/new", requirements={"id": "\d+"}, name="meals_feedback_new")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param Recipe $recipe
     * @return Response
     */
    public function newFeedbackAction(Request $request, Recipe $recipe): Response
    {
        $feedback = new RecipeFeedback($recipe);
        $form     = $this->createForm(MealFeedbackType::class, $feedback);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            if ($this->getUser() instanceof User) {
                $feedback->setCreatedBy($this->getUser());
            }
            $em->persist($feedback);
            $em->flush();
            
            return $this->redirectToRoute('meals_recipes_detail', ['id' => $recipe->getId()]);
        }
        
        return $this->render(
            'meals/recipe/feedback/new.html.twig',
            [
                'recipe' => $recipe,
                'form'   => $form->createView(),
            ]
        );
    }
    
    /**
     * Create a new acquisition attribute
     *
     * @Route("/admin/meals/recipes/new", name="meals_recipes_new")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $recipe = new Recipe('', '');
        $form   = $this->createForm(RecipeType::class, $recipe);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            if ($this->getUser() instanceof User) {
                $recipe->setCreatedBy($this->getUser());
            }
            $em->persist($recipe);
            $em->flush();
            
            return $this->redirectToRoute('meals_recipes_list');
        }
        
        return $this->render(
            'meals/recipe/new.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}