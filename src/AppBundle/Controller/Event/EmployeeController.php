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
use AppBundle\Form\EmployeeAssignUserType;
use AppBundle\Form\EmployeeType;
use AppBundle\JsonResponse;
use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class EmployeeController extends Controller
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
                if (!$fillout->getAttribute()->isUseAtEmployee()) {
                    continue;
                }

                $participantEntry['participation_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->getTextualValue(AttributeChoiceOption::PRESENTATION_MANAGEMENT_TITLE);
            }

            foreach ($employee->getAcquisitionAttributeFillouts() as $fillout) {
                if (!$fillout->getAttribute()->isUseAtEmployee()) {
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

        return $this->render(
            'event/admin/employee/detail.html.twig',
            [
                'commentManager'   => $commentManager,
                'employee'         => $employee,
                'event'            => $event,
                'similarEmployees' => $similarEmployees,
                'formAction'       => $formAction->createView(),
                'formAssignUser'   => $formUser->createView(),
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
