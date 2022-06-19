<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\AttendanceList;

use AppBundle\Entity\AttendanceList\AttendanceListColumn;
use AppBundle\Entity\AttendanceList\AttendanceListColumnChoice;
use AppBundle\Entity\AttendanceList\AttendanceListColumnRepository;
use AppBundle\Form\AttendanceListColumnType;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ConfigurationController extends AbstractController
{
    
    /**
     * @Route("/admin/attendance/columns", name="attendance_column_list")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function columnListAction(): Response
    {
        return $this->render('attendance/column-list.html.twig');
    }
    
    /**
     * @Route("/admin/attendance/columns-list.json", name="attendance_column_list_data")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function columnListDataAction(): Response
    {
        /** @var AttendanceListColumnRepository $repository */
        $repository = $this->getDoctrine()->getRepository(AttendanceListColumn::class);
        $result     = $repository->findAllForList();
        
        $row = null;
        foreach ($result as $columnId => &$row) {
            $row['column_id'] = $columnId;
            $row['title']     = $row['column']->getTitle();
            unset($row['column']);
        }
        unset($row);
        
        return new JsonResponse(array_values($result));
    }
    
    /**
     * Show column details
     *
     * @ParamConverter("column", class="AppBundle\Entity\AttendanceList\AttendanceListColumn", options={"id" =
     *                           "column_id"})
     * @Route("/admin/attendance/columns/{column_id}", requirements={"column_id": "\d+"},
     *                                                 name="attendance_column_detail")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     * @param AttendanceListColumn $column
     * @return Response
     */
    public function showColumnDetailAction(AttendanceListColumn $column): Response
    {
        return $this->render(
            'attendance/column-detail.html.twig',
            [
                'column' => $column,
            ]
        );
    }
    
    /**
     * Edit column
     *
     * @ParamConverter("column", class="AppBundle\Entity\AttendanceList\AttendanceListColumn", options={"id" =
     *                           "column_id"})
     * @Route("/admin/attendance/columns/{column_id}/edit", requirements={"column_id": "\d+"},
     *                                                      name="attendance_column_edit")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     * @param AttendanceListColumn $column
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editColumnAction(AttendanceListColumn $column, Request $request): Response
    {
        $form = $this->createForm(AttendanceListColumnType::class, $column);
        
        $originalChoices = new ArrayCollection();
        foreach ($column->getChoices() as $choice) {
            $originalChoices->add($choice);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em  = $this->getDoctrine()
                ->getManager();
            $now = new \DateTime();

            foreach ($originalChoices as $originalChoice) {
                $found = false;
                foreach ($column->getChoices(false) as $currentChoice) {
                    if ($currentChoice->getChoiceId() === $originalChoice->getChoiceId()) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $originalChoice->setDeletedAt($now);
                    $em->persist($originalChoice);
                }
            }
            
            $em->persist($column);
            $em->flush();
            
            return $this->redirectToRoute('attendance_column_detail', ['column_id' => $column->getColumnId()]);
        }
        
        return $this->render(
            'attendance/column-edit.html.twig',
            [
                'column' => $column,
                'form'   => $form->createView(),
            ]
        );
    }
    
    /**
     * Create a new acquisition attribute
     *
     * @Route("/admin/attendance/columns/new", name="attendance_column_new")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function columnCreateAction(Request $request): Response
    {
        $column = new AttendanceListColumn('');
        $column->addChoice(new AttendanceListColumnChoice(''));
        
        $form       = $this->createForm(AttendanceListColumnType::class, $column);
        $repository = $this->getDoctrine()->getRepository(AttendanceListColumn::class);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();
            
            $em->persist($column);
            $em->flush();
            
            return $this->redirectToRoute('attendance_column_list');
        }
        
        return $this->render(
            'attendance/column-new.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
    
    
}
