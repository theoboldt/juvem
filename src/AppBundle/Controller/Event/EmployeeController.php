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
use AppBundle\JsonResponse;
use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
     * @Route("/admin/event/{eid}/employee-list.json", requirements={"eid": "\d+"}, name="admin_event_employee_list_data")
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
     * @Route("/admin/event/{eid}/employee/{gid}", requirements={"eid": "\d+", "gid": "\d+"}, name="admin_employee_detail")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("employee", class="AppBundle:Employee", options={"id" = "gid"})
     * @Security("is_granted('employees_read', event)")
     */
    public function detailAction(Event $event, Employee $employee)
    {
        $commentManager = $this->container->get('app.comment_manager');
        
        return $this->render(
            'event/admin/employee/detail.html.twig',
            [
                'commentManager' => $commentManager,
                'employee'       => $employee,
                'event'          => $event,
            ]
        );
    }
    
    /**
     * Page for editing employee
     *
     * @Route("/admin/event/{eid}/employee/{gid}/edit", requirements={"eid": "\d+", "gid": "\d+"}, name="admin_employee_edit")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("employee", class="AppBundle:Employee", options={"id" = "gid"})
     * @Security("is_granted('employees_read', event)")
     */
    public function editAction(Event $event, Employee $employee)
    {
        return $this->render(
            'event/admin/employee/detail.html.twig',
            [
                'employee' => $employee,
                'event'    => $event,
            ]
        );
    }
    
    /**
     * Page for editing employees phone numbers
     *
     * @Route("/admin/event/{eid}/employee/{gid}/edit-phone", requirements={"eid": "\d+", "gid": "\d+"}, name="admin_employee_edit_phonenumbers")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("employee", class="AppBundle:Employee", options={"id" = "gid"})
     * @Security("is_granted('employees_read', event)")
     */
    public function editPhoneNumbersAction(Event $event, Employee $employee)
    {
        return $this->render(
            'event/admin/employee/detail.html.twig',
            [
                'employee' => $employee,
                'event'    => $event,
            ]
        );
    }
    
    
}