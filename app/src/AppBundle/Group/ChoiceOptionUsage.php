<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Group;


use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Doctrine\ORM\EntityManager;

class ChoiceOptionUsage implements ChoiceOptionUsageInterface
{

    /**
     * EntityManager
     *
     * @var EntityManager
     */
    private $em;

    /**
     * Related choice option
     *
     * @var AttributeChoiceOption
     */
    private $choiceOption = null;

    /**
     * Related event
     *
     * @var Event
     */
    private $event;

    /**
     * Assigned participations
     *
     * @var array|Participation[]
     */
    private $participations = null;

    /**
     * Ids of assigned participations
     *
     * @var array|int[]
     */
    private $participationIds = null;

    /**
     * Assigned participants
     *
     * @var array|Participant[]
     */
    private $participants = null;

    /**
     * Ids of assigned participants
     *
     * @var array|int[]
     */
    private $participantIds = null;

    /**
     * Assigned employees
     *
     * @var array|Employee[]
     */
    private $employees = null;

    /**
     * Ids of assigned employees
     *
     * @var array|int[]
     */
    private $employeeIds = null;

    /**
     * ChoiceOptionUsage constructor.
     *
     * @param EntityManager         $em           Entity manager
     * @param Event                 $event        Related event
     * @param AttributeChoiceOption $choiceOption Related choice option
     */
    public function __construct(
        EntityManager         $em,
        Event                 $event,
        AttributeChoiceOption $choiceOption
    ) {
        $this->em           = $em;
        $this->event        = $event;
        $this->choiceOption = $choiceOption;
    }

    /**
     * Fetch counts for usage from database
     */
    private function ensureIdsFetched()
    {
        if ($this->participationIds !== null) {
            return;
        }
        $attributeChoiceOptionUsage = new AttributeChoiceOptionUsageDistribution(
            $this->em, $this->event, $this->choiceOption->getAttribute()
        );
        $optionDistribution         = $attributeChoiceOptionUsage->getOptionDistribution($this->choiceOption);
        $this->participationIds     = $optionDistribution->getParticipationIds();
        $this->participantIds       = $optionDistribution->getParticipantIds();
        $this->employeeIds          = $optionDistribution->getEmployeeIds();
    }

    /**
     * Related choice option
     *
     * @return AttributeChoiceOption
     */
    public function getChoiceOption(): AttributeChoiceOption
    {
        return $this->choiceOption;
    }

    /**
     * Get all assigned @return Participation[]|array
     *
     * @see Participation
     *
     */
    public function getParticipations(): array
    {
        if ($this->participations === null) {
            $this->ensureIdsFetched();
            $this->participations = [];
            $repository           = $this->em->getRepository(Participation::class);
            $this->participations = $repository->participationsList(
                $this->event, true, true, $this->participationIds
            );
        }

        return $this->participations;
    }

    /**
     * Amount of assigned participations
     *
     * @return int
     */
    public function getParticipationCount(): int
    {
        return count($this->getParticipationIds());
    }

    /**
     * Get list of assigned participation ids
     *
     * @return array|int[]
     */
    public function getParticipationIds(): array
    {
        $this->ensureIdsFetched();
        return $this->participationIds;
    }

    /**
     * Get all assigned @return Participant[]|array
     *
     * @see Participant
     *
     */
    public function getParticipants(): array
    {
        if ($this->participants === null) {
            $this->ensureIdsFetched();
            $this->participants = [];
            $repository         = $this->em->getRepository(Participation::class);
            $this->participants = $repository->participantsList(
                $this->event, $this->participantIds, true, true
            );
        }


        return $this->participants;
    }

    /**
     * Determine if multiple participations assigned
     *
     * @return bool
     */
    public function hasParticipations(): bool
    {
        return $this->getParticipationCount() > 0;
    }

    /**
     * Amount of assigned participants
     *
     * @return int
     */
    public function getParticipantsCount(): int
    {
        return count($this->getParticipantIds());
    }

    /**
     * Get list of assigned participant ids
     *
     * @return array|int[]
     */
    public function getParticipantIds(): array
    {
        $this->ensureIdsFetched();
        return $this->participantIds;
    }

    /**
     * Determine if multiple participants assigned
     *
     * @return bool
     */
    public function hasParticipants(): bool
    {
        return $this->getParticipantsCount() > 0;
    }

    /**
     * Get all assigned @return Employee[]|array
     *
     * @see Employee
     *
     */
    public function getEmployees()
    {
        $this->ensureIdsFetched();
        $this->employees = [];
        $repository      = $this->em->getRepository(Employee::class);
        $this->employees = $repository->findForEvent(
            $this->event, $this->employeeIds, false
        );

        return $this->employees;
    }


    /**
     * Amount of assigned employees
     *
     * @return int
     */
    public function getEmployeeCount(): int
    {
        return count($this->getEmployeeIds());
    }

    /**
     * Get list of assigned employee ids
     *
     * @return array|int[]
     */
    public function getEmployeeIds(): array
    {
        $this->ensureIdsFetched();
        return $this->employeeIds;
    }

    /**
     * Determine if multiple employees assigned
     *
     * @return bool
     */
    public function hasEmployees(): bool
    {
        return $this->getEmployeeCount() > 0;
    }

    /**
     * Returns true if multiple assignments are available
     *
     * @return bool
     */
    public function hasMultipleAssignments(): bool
    {
        $assignments = 0;
        $assignments += $this->hasParticipations() ? 1 : 0;
        $assignments += $this->hasParticipants() ? 1 : 0;
        $assignments += $this->hasEmployees() ? 1 : 0;
        return $assignments > 1;
    }

    /**
     * Returns true if any assignment is available
     *
     * @return bool
     */
    public function hasAnyAssignment(): bool
    {
        return $this->hasParticipations() || $this->hasParticipants() || $this->hasEmployees();
    }

}
