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
use AppBundle\Entity\AcquisitionAttribute\Fillout;
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
        EntityManager $em,
        Event $event,
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
        $bid = $this->choiceOption->getAttribute()->getBid();
        $qb  = $this->em->createQueryBuilder();
        $qb->select(
            [
                'p.pid AS pid',
                'a.aid AS aid',
                'g.gid AS gid',
            ]
        )
           ->from(Fillout::class, 'f')
           ->leftJoin('f.participation', 'p')
           ->leftJoin('f.participant', 'a')
           ->leftJoin('a.participation', 'ap')
           ->leftJoin('f.employee', 'g')
           ->andWhere($qb->expr()->eq('f.attribute', $bid))
           ->andWhere(
               $qb->expr()->orX(
                   $qb->expr()->eq('f.value', ':option'),
                   $qb->expr()->like('f.value', ':likeOption')
               )
           )
           ->setParameter('option', $this->choiceOption->getId())
           ->setParameter('likeOption', "'%\'" . $this->choiceOption->getId() . "\'%'");

        $qb->andWhere(
            $qb->expr()->orX(
                'p.pid IS NULL',
                $qb->expr()->eq('p.event', $this->event->getEid())

            )
        );
        $qb->andWhere(
            $qb->expr()->orX(
                'a.aid IS NULL',
                $qb->expr()->eq('ap.event', $this->event->getEid())

            )
        );
        $qb->andWhere(
            $qb->expr()->orX(
                'g.gid IS NULL',
                $qb->expr()->eq('g.event', $this->event->getEid())

            )
        );

        $query = $qb->getQuery();

        $this->participationIds = [];
        $this->participantIds   = [];
        $this->employeeIds      = [];
        foreach ($query->execute() as $row) {
            if ($row['pid']) {
                $this->participationIds[] = $row['pid'];
            }
            if ($row['aid']) {
                $this->participantIds[] = $row['aid'];
            }
            if ($row['gid']) {
                $this->employeeIds[] = $row['gid'];
            }
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
     * Get all assigned @see Participation
     *
     * @return Participation[]|array
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
     * Get all assigned @see Participant
     *
     * @return Participant[]|array
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
     * Get all assigned @see Employee
     *
     * @return Employee[]|array
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
