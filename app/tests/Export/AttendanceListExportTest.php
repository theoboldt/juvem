<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Export;


use AppBundle\BitMask\ParticipantFood;
use AppBundle\Entity\AttendanceList\AttendanceList;
use AppBundle\Entity\AttendanceList\AttendanceListColumn;
use AppBundle\Entity\AttendanceList\AttendanceListColumnChoice;
use AppBundle\Entity\Participant;
use AppBundle\Export\AttendanceListExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\Export\Excel\ExportTestCase;

class AttendanceListExportTest extends ExportTestCase
{
    public function testExport(): void
    {
        $user  = $this->user();
        $event = $this->event();

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

        $list       = new AttendanceList($event);
        $reflection = new \ReflectionClass($list);
        $property   = $reflection->getProperty('tid');
        $property->setAccessible(true);
        $property->setValue($list, 1);

        $list->setTitle('Day 1');
        $list->setStartDate(new \DateTime('2000-10-01 10:00:00'));
        $list->addColumn($column);

        $lists = [$list];

        $attendanceData = [
            1 => [ //tid
                   1 => [ //aid
                          'columns' => [
                              1 => [
                                  'choice_id'   => 2,
                                  'comment'     => null,
                                  'created_at'  => null,
                                  'modified_at' => null,
                              ],
                          ],
                   ],
                   2 => [ //aid
                          'columns' => [
                              1 => [
                                  'choice_id'   => 1,
                                  'comment'     => null,
                                  'created_at'  => null,
                                  'modified_at' => null,
                              ],
                          ],
                   ],
            ],
        ];

        $export = new AttendanceListExport(
            $this->customization(),
            $lists,
            [$participant1, $participant2, $participant3],
            $attendanceData,
            $user,
            null
        );
        $export->setMetadata();

        $export->setMetadata();
        $export->process();

        $tmpPath       = __DIR__ . '/../../../var/tmp/' . uniqid('export_test');
        self::$files[] = $tmpPath;
        $export->write($tmpPath);

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet       = $spreadsheet->getSheet(0);
        $this->assertEquals('Day 1', $sheet->getTitle());
        $this->assertEqualsSheetValue($sheet, 2, 2, 'Testchild');
        $this->assertEqualsSheetValue($sheet, 3, 2, 'Doe');
        $this->assertEqualsSheetValue($sheet, 4, 2, 'U');

        $this->assertEqualsSheetValue($sheet, 2, 3, 'Secondchild');
        $this->assertEqualsSheetValue($sheet, 3, 3, 'Doe');
        $this->assertEqualsSheetValue($sheet, 4, 3, 'P');

        $this->assertEqualsSheetValue($sheet, 2, 4, 'Thirdchild');
        $this->assertEqualsSheetValue($sheet, 3, 4, 'Doe');
        $this->assertEqualsSheetValue($sheet, 4, 4, '');

        
        $properties = $spreadsheet->getProperties();
        $this->assertEquals('Juvem', $properties->getCategory());
        $this->assertEquals($user->fullname(), $properties->getCreator());
        $this->assertEquals($user->fullname(), $properties->getLastModifiedBy());
    }

    protected function assertEqualsSheetValue(Worksheet $sheet, int $columnIndex, int $row, string $expect): void
    {
        $cell = $sheet->getCellByColumnAndRow($columnIndex, $row);
        $given = $cell->getFormattedValue();
        $this->assertEquals($expect, $given);
    }

}
