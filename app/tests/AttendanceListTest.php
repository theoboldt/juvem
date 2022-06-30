<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use AppBundle\BitMask\ParticipantFood;
use AppBundle\Entity\AttendanceList\AttendanceList;
use AppBundle\Entity\AttendanceList\AttendanceListColumn;
use AppBundle\Entity\AttendanceList\AttendanceListColumnChoice;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Tests\Export\Excel\ParticipantTestingDataTrait;

class AttendanceListTest extends JuvemKernelTestCase
{
    use ParticipantTestingDataTrait;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $kernel = static::bootKernel();
        $kernel->boot();
    }

    /**
     * @return EntityManager
     */
    private function getEm(): EntityManager
    {
        $container = self::$kernel->getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');

        /** @var EntityManager $em */
        $em = $doctrine->getManager();
        return $em;
    }

    /**
     * @return Event
     */
    private function setupEvent(): Event
    {
        $event = $this->event();
        $em    = $this->getEm();
        $em->persist($event);
        $em->flush();
        return $event;
    }

    /**
     * @param Event $event
     * @return Participation
     */
    private function setupParticipation(Event $event): Participation
    {
        $participation1 = $this->participation($event);

        $participant1 = new Participant();
        $reflection   = new \ReflectionClass($participant1);
        $property     = $reflection->getProperty('aid');
        $property->setAccessible(true);
        $property->setValue($participant1, 1);
        $participant1->setNameLast('Doe');
        $participant1->setNameFirst('Testchild');
        $participant1->setBirthday(new \DateTime('2000-01-01 10:00:00'));
        $participant1->setFood(new ParticipantFood(4));
        $participant1->setGender(\AppBundle\Entity\Participant::LABEL_GENDER_FEMALE);
        $participant1->setInfoGeneral('Nothing special to know');
        $participant1->setInfoMedical('No medication needed');
        $participation1->addParticipant($participant1);

        $participant2 = new Participant();
        $reflection   = new \ReflectionClass($participant2);
        $property     = $reflection->getProperty('aid');
        $property->setAccessible(true);
        $property->setValue($participant2, 2);
        $participant2->setNameLast('Doe');
        $participant2->setNameFirst('Secondchild');
        $participant2->setBirthday(new \DateTime('2000-01-10 10:00:00'));
        $participant2->setFood(new ParticipantFood(4 + 2 + 8));
        $participant2->setGender(\AppBundle\Entity\Participant::LABEL_GENDER_MALE);
        $participation1->addParticipant($participant2);

        $participant3 = new Participant();
        $reflection   = new \ReflectionClass($participant3);
        $property     = $reflection->getProperty('aid');
        $property->setAccessible(true);
        $property->setValue($participant3, 3);
        $participant3->setNameLast('Doe');
        $participant3->setNameFirst('Thirdchild');
        $participant3->setBirthday(new \DateTime('2000-01-15 10:00:00'));
        $participant3->setFood(new ParticipantFood(2));
        $participant3->setGender(\AppBundle\Entity\Participant::LABEL_GENDER_MALE);
        $participation1->addParticipant($participant3);

        $em = $this->getEm();

        $em->persist($participation1);
        $em->persist($participant1);
        $em->persist($participant2);
        $em->persist($participant3);
        $em->flush();

        return $participation1;
    }

    /**
     * @return AttendanceListColumn
     */
    public function setupListColumn(): AttendanceListColumn
    {
        $column     = new AttendanceListColumn('Presence');
        $reflection = new \ReflectionClass($column);
        $property   = $reflection->getProperty('columnId');
        $property->setAccessible(true);
        $property->setValue($column, 1);

        $choice1    = new AttendanceListColumnChoice('Present');
        $reflection = new \ReflectionClass($choice1);
        $property   = $reflection->getProperty('choiceId');
        $property->setAccessible(true);
        $property->setValue($choice1, 1);
        $column->addChoice($choice1);

        $choice2  = new AttendanceListColumnChoice('Unavailable');
        $property = $reflection->getProperty('choiceId');
        $property->setAccessible(true);
        $property->setValue($choice2, 2);
        $column->addChoice($choice2);

        $em = $this->getEm();
        $em->persist($column);
        $em->flush();
        return $column;
    }

    /**
     * @param Event                $event
     * @param AttendanceListColumn $column
     * @return AttendanceList
     */
    public function setupList(Event $event, AttendanceListColumn $column): AttendanceList
    {
        $list       = new AttendanceList($event);
        $reflection = new \ReflectionClass($list);
        $property   = $reflection->getProperty('tid');
        $property->setAccessible(true);
        $property->setValue($list, 1);

        $list->setTitle('Test List');
        $list->setStartDate(new \DateTime('2000-10-01 10:00:00'));
        $list->addColumn($column);

        $em = $this->getEm();
        $em->persist($list);
        $em->flush();
        return $list;
    }

    /**
     * Ensure participants/participations do not get deleted when a column is removed from an attendance list
     *
     * @return void
     */
    public function testDeleteColumn(): void
    {
        $event           = $this->setupEvent();
        $participation   = $this->setupParticipation($event);
        $participationId = $participation->getId();
        $participantIds  = $participation->getParticipantsIdList();
        sort($participantIds);
        $column = $this->setupListColumn();
        $list   = $this->setupList($event, $column);

        $container = self::$kernel->getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');

        /** @var EntityManager $em */
        $em = $doctrine->getManager();

        $em->remove($column);
        $em->flush();

        $listAfter = $em->find(AttendanceList::class, $list->getTid());
        $this->assertFalse($listAfter->isDeleted());

        $this->assertParticipantsUnaffected($event->getEid(), $participationId, $participantIds);
    }

    /**
     * Ensure participants/participations do not get deleted when a column is removed from an attendance list
     *
     * @return void
     */
    public function testDeleteList(): void
    {
        $event           = $this->setupEvent();
        $participation   = $this->setupParticipation($event);
        $participationId = $participation->getId();
        $participantIds  = $participation->getParticipantsIdList();
        sort($participantIds);
        $column = $this->setupListColumn();
        $list   = $this->setupList($event, $column);

        $container = self::$kernel->getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');

        /** @var EntityManager $em */
        $em = $doctrine->getManager();

        $em->remove($list);
        $em->flush();

        $this->assertParticipantsUnaffected($event->getEid(), $participationId, $participantIds);
    }

    /**
     * @param int   $eid
     * @param int   $participationId
     * @param array $participantIds
     * @return void
     */
    private function assertParticipantsUnaffected(int $eid, int $participationId, array $participantIds): void
    {
        $container = self::$kernel->getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');

        /** @var EntityManager $em */
        $em = $doctrine->getManager();

        $eventAfter = $em->find(Event::class, $eid);
        $this->assertFalse($eventAfter->isDeleted());

        $participationAfter = $em->find(Participation::class, $participationId);
        $this->assertFalse($participationAfter->isDeleted());

        $qb = $em->createQueryBuilder();
        $qb->select('a')
           ->from(Participant::class, 'a')
           ->andWhere($qb->expr()->in('a.aid', $participantIds));
        $participantsAfter = $qb->getQuery()->execute();
        $this->assertCount(3, $participantsAfter);
        $participantIdsAfter = [];
        /** @var Participant $participantAfter */
        foreach ($participantsAfter as $participantAfter) {
            $participantIdsAfter[] = $participantAfter->getAid();
            $this->assertFalse(
                $participantAfter->isDeleted(), 'Participant with ID ' . $participantAfter->getAid() . ' got deleted'
            );
        }
        sort($participantIdsAfter);
        $this->assertEquals($participantIds, $participantIdsAfter);
    }
}
