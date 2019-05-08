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

class FetchedChoiceOptionUsage implements ChoiceOptionUsageInterface
{
    
    /**
     * Related choice option
     *
     * @var AttributeChoiceOption
     */
    private $choiceOption;
    
    /**
     * Ids of assigned participations
     *
     * @var array|int[]
     */
    private $participationIds = [];
    
    /**
     * Ids of assigned participants
     *
     * @var array|int[]
     */
    private $participantIds = [];
    
    /**
     * Ids of assigned employees
     *
     * @var array|int[]
     */
    private $employeeIds = [];
    
    /**
     * If set to true, no modifications are allwoed
     *
     * @var bool
     */
    private $frozen = false;
    
    /**
     * FetchedChoiceOptionUsage constructor.
     *
     * @param AttributeChoiceOption $choiceOption
     */
    public function __construct(AttributeChoiceOption $choiceOption)
    {
        $this->choiceOption = $choiceOption;
    }
    
    /**
     * Prevent further modifications
     */
    public function freeze() {
        if ($this->frozen) {
            throw new \RuntimeException('Already frozen');
        }
        $this->frozen = true;
    }
    
    
    /**
     * Add @see Participation id related to this usage
     *
     * @param int $id
     */
    public function addParticipationId(int $id): void
    {
        if ($this->frozen) {
            throw new \RuntimeException('Modifications forbidden');
        }
        if (!in_array($id, $this->participationIds)) {
            $this->participationIds[] = $id;
        }
    }
    
    /**
     * Add @see Participant id related to this usage
     *
     * @param int $id
     */
    public function addParticipantId(int $id): void
    {
        if ($this->frozen) {
            throw new \RuntimeException('Modifications forbidden');
        }
        if (!in_array($id, $this->participantIds)) {
            $this->participantIds[] = $id;
        }
    }
    
    /**
     * Add @see Employee id related to this usage
     *
     * @param int $id
     */
    public function addEmployeeId(int $id): void
    {
        if ($this->frozen) {
            throw new \RuntimeException('Modifications forbidden');
        }
        if (!in_array($id, $this->employeeIds)) {
            $this->employeeIds[] = $id;
        }
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
     * Amount of assigned participations
     *
     * @return int
     */
    public function getParticipationCount(): int
    {
        if (!$this->frozen) {
            throw new \RuntimeException('Collection of related items is not yet completed');
        }
        return count($this->participationIds);
    }
    
    /**
     * Determine if multiple participations assigned
     *
     * @return bool
     */
    public function hasParticipations(): bool
    {
        if (!$this->frozen) {
            throw new \RuntimeException('Collection of related items is not yet completed');
        }
        return count($this->participationIds) > 0;
    }
    
    /**
     * Amount of assigned participants
     *
     * @return int
     */
    public function getParticipantsCount(): int
    {
        if (!$this->frozen) {
            throw new \RuntimeException('Collection of related items is not yet completed');
        }
        return count($this->participantIds);
    }
    
    /**
     * Determine if multiple participants assigned
     *
     * @return bool
     */
    public function hasParticipants(): bool
    {
        if (!$this->frozen) {
            throw new \RuntimeException('Collection of related items is not yet completed');
        }
        return count($this->participantIds) > 0;
    }
    
    /**
     * Amount of assigned employees
     *
     * @return int
     */
    public function getEmployeeCount()
    {
        if (!$this->frozen) {
            throw new \RuntimeException('Collection of related items is not yet completed');
        }
        return count($this->employeeIds);
    }
    
    /**
     * Determine if multiple employees assigned
     *
     * @return bool
     */
    public function hasEmployees()
    {
        if (!$this->frozen) {
            throw new \RuntimeException('Collection of related items is not yet completed');
        }
        return count($this->employeeIds) > 0;
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
}