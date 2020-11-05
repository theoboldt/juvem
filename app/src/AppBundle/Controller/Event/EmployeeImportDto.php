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


use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;

class EmployeeImportDto
{
    
    /**
     * event
     *
     * @var Event
     */
    private $event;
    
    /**
     * Employees
     *
     * @var array|Employee[]
     */
    private $employees = [];
    
    /**
     * EmployeeImportDto constructor.
     *
     * @param Event $event
     * @param Employee[]|array $employees
     */
    public function __construct(Event $event, array $employees = [])
    {
        $this->event     = $event;
        $this->employees = $employees;
    }
    
    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }
    
    /**
     * @param Event $event
     * @return EmployeeImportDto
     */
    public function setEvent(Event $event): EmployeeImportDto
    {
        $this->event = $event;
        return $this;
    }
    
    /**
     * @return Employee[]|array
     */
    public function getEmployees()
    {
        return $this->employees;
    }
    
    /**
     * @param Employee[]|array $employees
     * @return EmployeeImportDto
     */
    public function setEmployees($employees)
    {
        $this->employees = $employees;
        return $this;
    }
    
}