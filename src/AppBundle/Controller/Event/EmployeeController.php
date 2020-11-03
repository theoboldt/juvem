<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event;

use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;

use AppBundle\Entity\PhoneNumber;
use AppBundle\Export\EmployeesExport;
use AppBundle\Form\EmployeeAssignUserType;
use AppBundle\Form\EmployeeType;
use AppBundle\Form\ImportEmployeesType;
use AppBundle\Form\MoveEmployeeType;
use AppBundle\JsonResponse;
use AppBundle\Manager\Payment\PaymentManager;
use AppBundle\ResponseHelper;
use AppBundle\Security\EventVoter;
use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class EmployeeController extends AbstractController
{
    /**
     * Page for list of employees of a single event
     *
     * @Route("/admin/event/{eid}/employee/list", requirements={"eid": "\d+"}, name="admin_event_employee_list")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     */
    public function listAction(Event $event)
    {
        return $this->render(
            'event/admin/employee/list.html.twig',
            [
                'event' => $event,
            ]
        );
    }
    
    
    /**
     * Export employee list
     *
     * @Route("/admin/event/{eid}/employee/export", requirements={"eid": "\d+"}, name="admin_employee_export")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('employees_read', event)")
     */
    public function exportAction(Event $event)
    {
        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $employees  = $repository->findForEvent($event);
        
        $export = new EmployeesExport(
            $this->get('app.twig_global_customization'), $event, $employees, $this->getUser()
        );
        $export->setMetadata();
        $export->process();
        
        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        ResponseHelper::configureAttachment(
            $response,
            $event->getTitle() . ' - Mitarbeiter.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        
        return $response;
    }

    /**
     * Page for list of employees of a single event
     *
     * @Route("/admin/event/{eid}/employee-list.json", requirements={"eid": "\d+"},
     *                                                 name="admin_event_employee_list_data")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('edit', event)")
     */
    public function listDataAction(Event $event)
    {
        $repository       = $this->getDoctrine()->getRepository(Employee::class);
        $employeeEntities = $repository->findForEvent($event);
        $result           = [];

        $phoneNumberUtil = PhoneNumberUtil::getInstance();

        /** @var Employee $employee */
        foreach ($employeeEntities as $employee) {
            $employeePhoneList = [];

            /** @var PhoneNumber $phoneNumberEntity */
            foreach ($employee->getPhoneNumbers() as $phoneNumberEntity) {
                /** @var \libphonenumber\PhoneNumber $phoneNumber */
                $phoneNumber         = $phoneNumberEntity->getNumber();
                $employeePhoneList[] = $phoneNumberUtil->formatOutOfCountryCallingNumber($phoneNumber, 'DE');
            }

            $participantEntry = [
                'gid'       => $employee->getGid(),
                'nameFirst' => $employee->getNameFirst(),
                'nameLast'  => $employee->getNameLast(),
                'phone'     => implode(', ', $employeePhoneList),
                'email'     => $employee->getEmail(),
            ];
            /** @var \AppBundle\Entity\AcquisitionAttribute\Fillout $fillout */
            foreach ($employee->getAcquisitionAttributeFillouts() as $fillout) {
                if (!$fillout->getAttribute()->getUseAtEmployee()) {
                    continue;
                }

                $participantEntry['participation_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
            }

            foreach ($employee->getAcquisitionAttributeFillouts() as $fillout) {
                if (!$fillout->getAttribute()->getUseAtEmployee()) {
                    continue;
                }
                $participantEntry['participant_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
            }

            $result[] = $participantEntry;
        }

        return new JsonResponse($result);
    }

    /**
     * Page for list of employees of a single event
     *
     * @Route("/admin/event/{eid}/employee/{gid}", requirements={"eid": "\d+", "gid": "\d+"},
     *                                             name="admin_employee_detail")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("employee", class="AppBundle:Employee", options={"id" = "gid"})
     * @Security("is_granted('employees_read', event)")
     */
    public function detailAction(Event $event, Employee $employee, Request $request)
    {
        $commentManager = $this->container->get('app.comment_manager');

        $formAction = $this->createFormBuilder()
                           ->add('action', HiddenType::class)
                           ->getForm();
        $formUser   = $this->createForm(EmployeeAssignUserType::class, $employee);

        $employeeChanged = false;
        $formAction->handleRequest($request);
        if ($formAction->isSubmitted() && $formAction->isValid()) {
            $action = $formAction->get('action')->getData();
            switch ($action) {
                case 'delete':
                    $employee->setDeletedAt(new \DateTime());
                    break;
                case 'restore':
                    $employee->setDeletedAt(null);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $employeeChanged = true;
        } else {
            $formUser->handleRequest($request);
            if ($formUser->isSubmitted() && $formUser->isValid()) {
                $employeeChanged = true;

            }
        }
        $formMoveEmployee = $this->createForm(
            MoveEmployeeType::class, null, [MoveEmployeeType::EMPLOYEE_OPTION => $employee]
        );
        $formMoveEmployee->handleRequest($request);
        if ($formMoveEmployee->isSubmitted() && $formMoveEmployee->isValid()) {
            
            $employeeNew = $this->get('app.participation_manager')->moveEmployee(
                $employee,
                $formMoveEmployee->get('targetEvent')->getData(),
                $formMoveEmployee->get('commentOldEmployee')->getData(),
                $formMoveEmployee->get('commentNewEmployee')->getData(),
                $this->getUser()
            );
            return $this->redirectToRoute(
                'admin_employee_detail',
                [
                    'eid' => $event->getEid(),
                    'gid' => $employeeNew->getGid(),
                ]
            );
        }

        if ($employeeChanged) {
            $this->denyAccessUnlessGranted('employees_edit', $event);
            $em = $this->getDoctrine()->getManager();
            $em->persist($employee);
            $em->flush();
            return $this->redirectToRoute(
                'admin_employee_detail',
                [
                    'eid' => $event->getEid(),
                    'gid' => $employee->getGid(),
                ]
            );
        }

        $employeeRepository = $this->getDoctrine()->getRepository(Employee::class);
        $similarEmployees   = $employeeRepository->relatedEmployees($employee);

        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->get('app.payment_manager');
        $priceTag       = $paymentManager->getEntityPriceTag($employee);
        $summands       = $priceTag->getSummands();

        return $this->render(
            'event/admin/employee/detail.html.twig',
            [
                'commentManager'   => $commentManager,
                'employee'         => $employee,
                'summands'         => $summands,
                'summandsTotal'    => $priceTag->getPrice(true),
                'event'            => $event,
                'similarEmployees' => $similarEmployees,
                'formAction'       => $formAction->createView(),
                'formAssignUser'   => $formUser->createView(),
                'formMoveEmployee' => $formMoveEmployee->createView(),
            ]
        );
    }

    /**
     * Page for editing employee
     *
     * @Route("/admin/event/{eid}/employee/{gid}/edit", requirements={"eid": "\d+", "gid": "\d+"},
     *                                                  name="admin_employee_edit")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("employee", class="AppBundle:Employee", options={"id" = "gid"})
     * @Security("is_granted('employees_read', event)")
     */
    public function editAction(Event $event, Employee $employee, Request $request)
    {

        $form = $this->createForm(
            EmployeeType::class,
            $employee,
            [
                EmployeeType::ACQUISITION_FIELD_PUBLIC  => true,
                EmployeeType::ACQUISITION_FIELD_PRIVATE => true,
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($employee);
            $em->flush();

            return $this->redirectToRoute(
                'admin_employee_detail',
                [
                    'eid' => $event->getEid(),
                    'gid' => $employee->getGid(),
                ]
            );
        }

        return $this->render(
            'event/admin/employee/edit.html.twig',
            [
                'form'              => $form->createView(),
                'employee'          => $employee,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, false, true, true, true),
            ]
        );
    }
   
    /**
     * Page for importing employee
     *
     * @Route("/admin/event/{eid}/employee/import", requirements={"eid": "\d+"}, methods={"GET"}, name="admin_employee_import_proposals")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('employees_edit', event)")
     */
    public function importProposalsAction(Event $event): Response
    {
        //fetch all events for related data
        $this->getDoctrine()->getRepository(Event::class)->findAll();
        
        $targetEventId          = $event->getEid();
        $excludePredecessorList = [];
        $employees              = [];
        /** @var Employee $employee */
    
        foreach ($this->getDoctrine()->getRepository(Employee::class)->findBy(['deletedAt' => null]) as $employee) {
            if ($employee->getEvent()->getEid() === $targetEventId) {
                if ($employee->hasPredecessor()) {
                    $excludePredecessorList[] = $employee->getPredecessor()->getGid();
                }
                $excludePredecessorList[] = $targetEventId;
                continue;
            }
            if ($this->get('security.authorization_checker')
                     ->isGranted(EventVoter::EMPLOYEES_READ, $employee->getEvent())
            ) {
                $employees[$employee->getGid()] = $employee;
            }
            if ($employee->hasPredecessor()) {
                $predecessorId = $employee->getPredecessor()->getGid();
                if (isset($employees[$predecessorId])) {
                    unset($employees[$predecessorId]); //only accept the latest version of an employee
                }
            }
        }
        $employees = $this->removeExcludedPredecessors($employees, $excludePredecessorList);
        
        return $this->render(
            'event/admin/employee/import-proposals.html.twig',
            [
                'event'     => $event,
                'employees' => $employees,
            ]
        );
    }
    
    
    /**
     * Remove employees because of their predecessor connection
     *
     * @see importProposalsAction()
     * @param array $employeeList
     * @param array $excludePredecessorList
     * @return array
     */
    private function removeExcludedPredecessors(array $employeeList, array $excludePredecessorList)
    {
        $changed = false;
        /** @var Employee $employee */
        foreach ($employeeList as $employeeId => $employee) {
            if (in_array($employeeId, $excludePredecessorList)) {
                $excludePredecessorList[] = $employeeId;
                if ($employee->hasPredecessor()) {
                    $excludePredecessorList[] = $employee->getPredecessor()->getGid();
                }
                unset($employeeList[$employeeId]);
                $changed = true;
            }
        }
        
        if ($changed) {
            return $this->removeExcludedPredecessors($employeeList, $excludePredecessorList);
        }
        return $employeeList;
    }
    
    /**
     * Page for importing employee
     *
     * @Route("/admin/event/{eid}/employee/import", requirements={"eid": "\d+"}, methods={"POST"}, name="admin_employee_import")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('employees_edit', event)")
     */
    public function importAction(Event $event, Request $request): Response
    {
        $proposals         = array_keys($request->get('employee_proposals', []));
        $proposedEmployees = $this->getDoctrine()->getRepository(Employee::class)->findByIdList($proposals);
        $employees         = [];
        /** @var Employee $employee */
        foreach ($proposedEmployees as $employee) {
            if ($this->isGranted('employees_read', $employee->getEvent())) {
                $employees[] = Employee::createFromTemplateForEvent($employee, $event, true);
            }
        }
    
        $import = new EmployeeImportDto($event, $employees);
        $form   = $this->createForm(
            ImportEmployeesType::class,
            $import,
            [
                EmployeeType::EVENT_OPTION => $event
            ]
        );
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $employees = $form->get('employees')->getData();
            $em        = $this->getDoctrine()->getManager();
            foreach ($employees as $employee) {
                $em->persist($employee);
            }
            $em->flush();
            $this->addFlash(
                'success',
                sprintf('%d Mitarbeiter importiert', count($employees))
            );
            
            return $this->redirectToRoute(
                'admin_event_employee_list',
                [
                    'eid' => $event->getEid()
                ]
            );
        }
        
        return $this->render(
            'event/admin/employee/import-form.html.twig',
            [
                'form'  => $form->createView(),
                'event' => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, false, true, true, true),
            ]
        );
    }
    /**
     * Page for editing employee
     *
     * @Route("/admin/event/{eid}/employee/create", requirements={"eid": "\d+"}, name="admin_employee_create")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('employees_read', event)")
     */
    public function createAction(Event $event, Request $request)
    {
        $employee = new Employee($event);

        $form = $this->createForm(
            EmployeeType::class,
            $employee,
            [
                EmployeeType::ACQUISITION_FIELD_PUBLIC  => true,
                EmployeeType::ACQUISITION_FIELD_PRIVATE => true,
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($employee);
            $em->flush();

            return $this->redirectToRoute(
                'admin_employee_detail',
                [
                    'eid' => $event->getEid(),
                    'gid' => $employee->getGid(),
                ]
            );
        }

        return $this->render(
            'event/admin/employee/new.html.twig',
            [
                'form'  => $form->createView(),
                'event' => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, false, true, true, true),
            ]
        );
    }
}
