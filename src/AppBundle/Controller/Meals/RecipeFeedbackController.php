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
use AppBundle\Entity\Meals\QuantityUnit;
use AppBundle\Entity\Meals\Recipe;
use AppBundle\Entity\Meals\RecipeFeedback;
use AppBundle\Entity\User;
use AppBundle\Form\Meal\MealFeedbackType;
use AppBundle\Form\Meal\RecipeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RecipeFeedbackController extends Controller
{
    /**
     * @ParamConverter("recipe", class="AppBundle\Entity\Meals\Recipe")
     * @Route("/admin/meals/recipes/{id}/feedback", requirements={"id": "\d+"}, name="meals_feedback_list")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Recipe $recipe
     * @return Response
     */
    public function listAction(Recipe $recipe)
    {
        return $this->render('meals/recipe/feedback/list.html.twig', ['recipe' => $recipe]);
    }
    
    /**
     * Data provider for event list grid
     *
     * @Route("/admin/meals/recipes/{id}/list.json", requirements={"id": "\d+"}, name="meals_feedback_list_data")
     * @ParamConverter("recipe", class="AppBundle\Entity\Meals\Recipe")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listDataAction(Recipe $recipe)
    {
        $repository   = $this->getDoctrine()->getRepository(RecipeFeedback::class);
        $feedbackList = $repository->findBy(['recipe' => $recipe->getId()], ['date' => 'DESC']);
        
        $result = [];
        /** @var RecipeFeedback $feedback */
        foreach ($feedbackList as $feedback) {
            
            $eventContent = null;
            $event        = $feedback->getEvent();
            if ($event) {
                $eventUrl = $this->get('router')->generate('event', ['eid' => $event->getEid()]);
                
                $eventContent = '<a href="' . $eventUrl . '" target="_blank">' . $event->getTitle() . '</a>';
            }
            $result[] = [
                'id'              => $feedback->getId(),
                'date'            => $feedback->getDate()->format(Event::DATE_FORMAT_DATE),
                'feedback_global' => $feedback->getFeedbackGlobal(true),
                'event'           => $eventContent,
            ];
        }
        
        return new JsonResponse($result);
    }
    
    /**
     * @ParamConverter("recipe", class="AppBundle\Entity\Meals\Recipe", options={"id" = "rid"})
     * @ParamConverter("feedback", class="AppBundle\Entity\Meals\RecipeFeedback", options={"id" = "fid"})
     * @Route("/admin/meals/recipes/{rid}/feedback/{fid}", requirements={"rid": "\d+","fid": "\d+"}, name="meals_feedback_detail")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function detailAction(Recipe $recipe, RecipeFeedback $feedback)
    {
        
        $title = 'Rückmeldung vom ' . $feedback->getDate()->format(Event::DATE_FORMAT_DATE);
        $event = $feedback->getEvent();
        if ($event) {
            $title .= '(' . $event->getTitle() . ')';
        }
        
        return $this->render(
            'meals/recipe/feedback/detail.html.twig',
            [
                'title'    => $title,
                'recipe'   => $recipe,
                'feedback' => $feedback,
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
    public function newAction(Request $request, Recipe $recipe): Response
    {
        $feedback = new RecipeFeedback($recipe);
        
        $form  = $this->createForm(MealFeedbackType::class, $feedback);
        $units = [];
        /** @var QuantityUnit $unit */
        foreach ($this->getDoctrine()->getRepository(QuantityUnit::class)->findAll() as $unit) {
            $units[$unit->getId()] = $unit;
        };
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            if ($this->getUser() instanceof User) {
                $feedback->setCreatedBy($this->getUser());
            }
            $this->addFlash(
                'success',
                'Die Rückmeldung wurde erfasst.'
            );
            $em->persist($feedback);
            $em->flush();
            
            return $this->redirectToRoute('meals_recipes_detail', ['id' => $recipe->getId()]);
        }
        
        return $this->render(
            'meals/recipe/feedback/new.html.twig',
            [
                'units'  => $units,
                'recipe' => $recipe,
                'form'   => $form->createView(),
            ]
        );
    }
}