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
use AppBundle\Form\AcquisitionFormulaType;
use AppBundle\Form\AcquisitionType;
use AppBundle\Form\GroupType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AcquisitionController extends AbstractController
{
    /**
     * @Route("/admin/acquisition/list", name="acquisition_list")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function listAction(Request $request)
    {
        return $this->render('acquisition/list.html.twig');
    }


    /**
     * Data provider for event list grid
     *
     * @Route("/admin/acquisition/list.json", name="acquisition_list_data")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function listDataAction(Request $request)
    {
        $repository          = $this->getDoctrine()->getRepository(Attribute::class);
        $attributeEntityList = $repository->findBy(['deletedAt' => null]);

        $attributeList = [];
        /** @var \AppBundle\Entity\AcquisitionAttribute\Attribute $attribute */
        foreach ($attributeEntityList as $attribute) {

            $attributeList[] = [
                'bid'                    => $attribute->getBid(),
                'management_title'       => $attribute->getManagementTitle(),
                'management_description' => $attribute->getManagementDescription(),
                'type'                   => $attribute->getFieldType(true),
                'form_title'             => $attribute->getFormTitle(),
                'form_description'       => $attribute->getFormDescription(),
                'formula_enabled'        => $attribute->isPriceFormulaEnabled() ? 'ja' : 'nein',
                'formula'                => $attribute->getPriceFormula(),
                'is_deleted'             => $attribute->isDeleted() ? 1 : 0,
            ];
        }


        return new JsonResponse($attributeList);
    }

    /**
     * Detail page for a acquisition attribute
     *
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @Route("/admin/acquisition/{bid}", requirements={"bid": "\d+"}, name="acquisition_detail")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function detailAction(Request $request, Attribute $attribute)
    {
        $formAction = $this->createFormBuilder()
                           ->add('action', HiddenType::class)
                           ->getForm();

        $formAction->handleRequest($request);
        if ($formAction->isSubmitted() && $formAction->isValid()) {
            $action = $formAction->get('action')
                                 ->getData();
            switch ($action) {
                case 'delete':
                    $attribute->setDeletedAt(new \DateTime());
                    break;
                case 'restore':
                    $attribute->setDeletedAt(null);
                    break;
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($attribute);
            $em->flush();
            return $this->redirectToRoute('acquisition_detail', ['bid' => $attribute->getBid()]);
        }

        return $this->render(
            'acquisition/detail.html.twig',
            [
                'form'        => $formAction->createView(),
                'acquisition' => $attribute,
                'events'      => $attribute->getEvents(),
            ]
        );
    }

    /**
     * Edit page for formula
     *
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @Route("/admin/acquisition/{bid}/edit/formula", requirements={"bid": "\d+"}, name="acquisition_edit_formula")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function editFormulaAction(Request $request, Attribute $attribute)
    {
        $form             = $this->createForm(AcquisitionFormulaType::class, $attribute);
        $variableProvider = $this->get('app.price.formula_variable_provider');

        #$resolver = $this->get('app.payment.formula_resolver');
    #$dependencies = $resolver->getDependenciesFor($attribute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($attribute);
            $em->flush();

            return $this->redirectToRoute('acquisition_detail', ['bid' => $attribute->getBid()]);
        }
        $fieldType = $attribute->getFieldType();

        return $this->render(
            'acquisition/edit-formula.html.twig',
            [
                'form'                => $form->createView(),
                'acquisition'         => $attribute,
                'variables'           => $variableProvider->variables($attribute),
                'showChoiceVariables' => ($fieldType == ChoiceType::class || $fieldType == GroupType::class),
                'showNumberVariables' => ($fieldType == NumberType::class),
            ]
        );
    }


    /**
     * Edit page for one single attribute
     *
     * @ParamConverter("attribute", class="AppBundle\Entity\AcquisitionAttribute\Attribute", options={"id" = "bid"})
     * @Route("/admin/acquisition/{bid}/edit", requirements={"bid": "\d+"}, name="acquisition_edit")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function editAction(Request $request, Attribute $attribute)
    {
        $form              = $this->createForm(AcquisitionType::class, $attribute);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            /** @var AttributeChoiceOption $option */
            foreach ($attribute->getChoiceOptions() as $option) {
                $option->setAttribute($attribute);
            }

            $em->persist($attribute);
            $em->flush();

            return $this->redirectToRoute('acquisition_detail', ['bid' => $attribute->getBid()]);
        }

        $fieldType = $attribute->getFieldType();

        return $this->render(
            'acquisition/edit.html.twig',
            [
                'acquisition'               => $attribute,
                'showChoiceOptions'         => ($fieldType == ChoiceType::class || $fieldType == GroupType::class),
                'showChoiceMultipleOptions' => ($fieldType == ChoiceType::class),
                'form'                      => $form->createView(),
            ]
        );
    }

    /**
     * Create a new acquisition attribute
     *
     * @Route("/admin/acquisition/new", name="acquisition_new")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function newAction(Request $request)
    {
        $attribute = new Attribute();

        $form              = $this->createForm(AcquisitionType::class, $attribute);
        $repository        = $this->getDoctrine()->getRepository(Attribute::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($attribute);
            $em->flush();

            return $this->redirectToRoute('acquisition_list');
        }
    
        return $this->render(
            'acquisition/new.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
