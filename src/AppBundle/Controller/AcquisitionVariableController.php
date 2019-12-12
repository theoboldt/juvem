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
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariableValue;
use AppBundle\Entity\AcquisitionAttribute\Variable\VariableRepository;
use AppBundle\Entity\Event;
use AppBundle\Form\AcquisitionAttribute\EventSpecificVariableType;
use AppBundle\Form\AcquisitionAttribute\SpecifyEventSpecificVariableValuesForVariableType;
use AppBundle\Form\AcquisitionFormulaType;
use AppBundle\Form\AcquisitionType;
use AppBundle\Form\GroupType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Form;
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
     * @Route("/admin/variable/list", name="admin_variable_list", methods={"GET"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listAction(Request $request)
    {
        return $this->render('acquisition/variable/list.html.twig');
    }
    
    /**
     * Data provider for event list grid
     *
     * @Route("/admin/variable/list.json", name="admin_variable_list_data", methods={"GET"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listDataAction(Request $request)
    {
        $repository         = $this->getDoctrine()->getRepository(EventSpecificVariable::class);
        $variableEntityList = $repository->findAll();
        
        $variableList = [];
        /** @var EventSpecificVariable $variable */
        foreach ($variableEntityList as $variable) {
            $variableList[] = [
                'id'          => $variable->getId(),
                'description' => $variable->getDescription(),
                'variable'    => $variable->getFormulaVariable(),
                'is_deleted'  => $variable->isDeleted() ? 1 : 0,
            ];
        }
        
        
        return new JsonResponse($variableList);
    }
    
    /**
     * Create a new variable
     *
     * @Route("/admin/variable/new", name="admin_variable_new")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function newAction(Request $request): Response
    {
        $form = $this->createForm(
            EventSpecificVariableType::class, null
        );
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /** @var EventSpecificVariable $variable */
            $variable = $form->getData();
            
            $em->persist($variable);
            $em->flush();
            
            $events = $attribute->getEvents();
            $this->addFlash(
                'success',
                'Variable wurde erstellt'
            );
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
     * Edit a variable
     *
     * @Route("/admin/variable/{vid}/edit", requirements={"vid": "\d+"}, name="admin_variable_edit")
     * @ParamConverter("variable", class="AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable", options={"id" = "vid"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editAction(Request $request, EventSpecificVariable $variable): Response
    {
        $form = $this->createForm(
            EventSpecificVariableType::class, $variable
        );
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /** @var EventSpecificVariable $variable */
            $variable = $form->getData();
            
            $em->persist($variable);
            $em->flush();
            
            $this->addFlash(
                'success',
                'Die Änderungen an der Variable wurden gespeichert'
            );
                return $this->redirectToRoute(
                    'admin_variable_configure', ['vid' => $variable->getId()]
                );
        }
        
        return $this->render(
            'acquisition/variable/edit.html.twig',
            [
                'variable'    => $variable,
                'form'        => $form->createView()
            ]
        );
    }
    
    /**
     * Check if a variable configuration warning should be generated and if so, display it
     *
     * @param EventSpecificVariable $variable Variable
     * @return void
     */
    public function generateVariableConfigureWarningIfRequired(EventSpecificVariable $variable): void
    {
        return;
        //TODO
        if (!$variable->hasDefaultValue()) {
            $attribute = $variable->getAttribute();
            $events    = $attribute->getEvents();
            
            $missing = [];
            /** @var Event $event */
            foreach ($events as $event) {
                $eid = $event->getEid();
                $vid = $variable->getId();
                
                $values    = $variable->getValues();
                $available = false;
                /** @var EventSpecificVariableValue $value */
                foreach ($values as $value) {
                    if ($value->getEvent()->getEid() === $eid
                        && $value->getVariable()->getId() === $vid
                    ) {
                        $available = true;
                        break;
                    }
                }
                if (!$available) {
                    $missing[] = $event;
                }
            }
            
            if ($missing) {
                $this->addFlash(
                    'warning',
                    sprintf(
                        'Obwohl für diese Variable kein Standardwert konfiguriert ist, sind bei %d Veranstaltungen keine Werte eingestellt. Sie sollten die <a href="%s">Werte für die Variable <i>%s</i> umgehend konfigurieren</a>.',
                        count($missing),
                        $this->generateUrl(
                            'acquisition_variable_configure',
                            ['bid' => $attribute->getBid(), 'vid' => $variable->getId()]
                        ),
                        $variable->getFormulaVariable()
                    )
                );
            }
        }
    }
    
    /**
     * Configure values for all events where this variable is used
     *
     * @Route("/admin/variable/{vid}", requirements={"vid": "\d+"}, name="admin_variable_detail")
     * @ParamConverter("variable", class="AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable", options={"id" = "vid"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function showEventValuesAction(Request $request, EventSpecificVariable $variable): Response
    {
        $form = $this->createFormBuilder()
                     ->add('action', HiddenType::class)
                     ->getForm();
        $em   = $this->getDoctrine()->getManager();
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->get('action')->getData();
            
            switch ($action) {
                case 'delete':
                    $variable->setDeletedAt(new \DateTime());
                    $this->addFlash(
                        'success',
                        'Die Variable wurde in den Papierkorb verschoben'
                    );
                    break;
                case 'restore':
                    $variable->setDeletedAt(null);
                    $this->addFlash(
                        'success',
                        'Die Variable wurde wiederhergestellt'
                    );
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $em->persist($variable);
            $em->flush();
            return $this->redirectToRoute('admin_variable_list');
        }
    
        $eventRepository = $this->getDoctrine()->getRepository(Event::class);
        $events          = $eventRepository->findAllOrderedByDate();
        /** @var EventSpecificVariableValue $value */
        $values = [];
        
        foreach ($variable->getValues() as $value) {
            $values[$value->getEvent()->getEid()] = $value->getValue();
        }
        $this->generateVariableConfigureWarningIfRequired($variable);
        
        return $this->render(
            'acquisition/variable/detail.html.twig',
            [
                'form'        => $form->createView(),
                'variable'    => $variable,
                'events'      => $events,
                'values'      => $values
            ]
        );
    }
    
    /**
     * Configure values for all events where this variable is used
     *
     * @Route("/admin/variable/{vid}/configure", requirements={"vid": "\d+"}, name="admin_variable_configure")
     * @ParamConverter("variable", class="AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable", options={"id" = "vid"})
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function configureEventValuesAction(Request $request, EventSpecificVariable $variable): Response
    {
        $eventRepository = $this->getDoctrine()->getRepository(Event::class);
        $events          = $eventRepository->findAllOrderedByDate();
    
        $form = $this->createForm(
            SpecifyEventSpecificVariableValuesForVariableType::class,
            null,
            [
                SpecifyEventSpecificVariableValuesForVariableType::FIELD_VARIABLE => $variable,
                SpecifyEventSpecificVariableValuesForVariableType::FIELD_EVENTS   => $events,
            ]
        );
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            
            /** @var Form $formElement */
            foreach ($form as $formElement) {
                /** @var EventSpecificVariableValue $variableValue */
                $variableValue = $formElement->getData();
                if ($variableValue->getValue() !== null) {
                    $em->persist($variableValue);
                } elseif ($variable->hasValue($variableValue)) {
                    $variable->removeValue($variableValue);
                    $em->remove($variableValue);
                    
                }
            }
            $em->flush();
            $this->addFlash(
                'success',
                'Die Werte für die Veranstaltungen wurden gespeichert'
            );
            return $this->redirectToRoute(
                'admin_variable_detail', ['vid' => $variable->getId()]
            );
        }
        
        return $this->render(
            'acquisition/variable/configure.html.twig',
            [
                'variable'    => $variable,
                'form'        => $form->createView(),
            ]
        );
    }
}
