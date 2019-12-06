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

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use AppBundle\Form\AcquisitionAttribute\EventSpecificVariableType;
use AppBundle\Form\AcquisitionFormulaType;
use AppBundle\Form\AcquisitionType;
use AppBundle\Form\GroupType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AcquisitionVariableController extends Controller
{
    
    /**
     * Create a new variable
     *
     * @Route("/admin/acquisition/{bid}/variable/new", requirements={"bid": "\d+"}, name="acquisition_variable_new")
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function newAction(Request $request, Attribute $attribute): Response
    {
        $form = $this->createForm(
            EventSpecificVariableType::class, null, [EventSpecificVariableType::FIELD_ATTRIBUTE => $attribute]
        );
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /** @var EventSpecificVariable $variable */
            $variable = $form->getData();
            
            $em->persist($variable);
            $em->flush();
            
            $events = $attribute->getEvents();
            if (count($events)) {
                return $this->redirectToRoute(
                    'acquisition_variable_configure', ['bid' => $attribute->getBid(), 'vid' => $variable->getId()]
                );
            }
        }
        
        return $this->render(
            'acquisition/variable/new.html.twig',
            [
                'acquisition' => $attribute,
                'form'        => $form->createView()
            ]
        );
    }
    
    /**
     * Configure values for all events where this variable is used
     *
     * @Route("/admin/acquisition/{bid}/variable/{vid}", requirements={"bid": "\d+", "vid": "\d+"}, name="acquisition_variable_configure")
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @ParamConverter("variable", class="AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable", options={"id" = "vid"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function configureEventValuesAction(Request $request, Attribute $attribute, EventSpecificVariable $variable
    ): Response
    {
        return $this->render(
            'acquisition/variable/values_configure.html.twig',
            [
                'acquisition' => $attribute,
                'variable'    => $variable,
            
            ]
        );
    }
}
