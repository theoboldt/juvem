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

interface ChoiceOptionUsageInterface
{
    
    /**
     * Related choice option
     *
     * @return AttributeChoiceOption
     */
    public function getChoiceOption(): AttributeChoiceOption;
    
    /**
     * Amount of assigned participations
     *
     * @return int
     */
    public function getParticipationCount(): int;
    
    /**
     * Determine if multiple participations assigned
     *
     * @return bool
     */
    public function hasParticipations(): bool;
    
    /**
     * Amount of assigned participants
     *
     * @return int
     */
    public function getParticipantsCount(): int;
    
    /**
     * Determine if multiple participants assigned
     *
     * @return bool
     */
    public function hasParticipants(): bool;
    
    /**
     * Amount of assigned employees
     *
     * @return int
     */
    public function getEmployeeCount();
    
    /**
     * Determine if multiple employees assigned
     *
     * @return bool
     */
    public function hasEmployees();
    
    /**
     * Returns true if multiple assignments are available
     *
     * @return bool
     */
    public function hasMultipleAssignments();
    
}