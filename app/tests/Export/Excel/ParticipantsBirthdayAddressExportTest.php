<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Export\Excel;


use AppBundle\BitMask\ParticipantFood;
use AppBundle\Entity\Participant;
use AppBundle\Export\ParticipantsBirthdayAddressExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParticipantsBirthdayAddressExportTest extends ExportTestCase
{

    public function testParticipationsExport(): void
    {
        $user  = $this->user();
        $event = $this->event();

        $participation1 = $this->participation($event);

        $participant1 = new Participant();
        $participant1->setNameLast('Doe');
        $participant1->setNameFirst('Testchild');
        $participant1->setBirthday(new \DateTime('2000-01-01 10:00:00'));
        $participant1->setFood(new ParticipantFood(4));
        $participant1->setGender(2);
        $participant1->setInfoGeneral('Nothing special to know');
        $participant1->setInfoMedical('No medication needed');
        $participation1->addParticipant($participant1);

        $participant2 = new Participant();
        $participant2->setNameLast('Doe');
        $participant2->setNameFirst('Secondchild');
        $participant2->setBirthday(new \DateTime('2000-01-10 10:00:00'));
        $participant2->setFood(new ParticipantFood(4 + 2 + 8));
        $participant2->setGender(1);
        $participation1->addParticipant($participant2);

        $participant3 = new Participant();
        $participant3->setNameLast('Doe');
        $participant3->setNameFirst('Thirdchild');
        $participant3->setBirthday(new \DateTime('2000-01-15 10:00:00'));
        $participant3->setFood(new ParticipantFood(2));
        $participant3->setGender(1);
        $participation1->addParticipant($participant3);

        $export = new ParticipantsBirthdayAddressExport(
            $this->customization(), $event, [$participant1, $participant2, $participant3], $user
        );
        $export->setMetadata();

        $export->setMetadata();
        $export->process();

        $tmpPath       = __DIR__ . '/../../../../var/tmp/' . uniqid('export_test');
        self::$files[] = $tmpPath;
        $export->write($tmpPath);

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet       = $spreadsheet->getSheet(0);
        $this->assertEqualsSheetValue($sheet, 1, 2, 'Testchild');
        $this->assertEqualsSheetValue($sheet, 2, 2, 'Doe');
        $this->assertEqualsSheetValue($sheet, 3, 2, '01.01.2000');
        $this->assertEqualsSheetValue($sheet, 4, 2, 'Musterstrasse 25, 70000 Musterstadt');

        $this->assertEqualsSheetValue($sheet, 1, 3, 'Secondchild');
        $this->assertEqualsSheetValue($sheet, 2, 3, 'Doe');
        $this->assertEqualsSheetValue($sheet, 3, 3, '10.01.2000');
        $this->assertEqualsSheetValue($sheet, 4, 3, 'Musterstrasse 25, 70000 Musterstadt');

        $this->assertEqualsSheetValue($sheet, 1, 4, 'Thirdchild');
        $this->assertEqualsSheetValue($sheet, 2, 4, 'Doe');
        $this->assertEqualsSheetValue($sheet, 3, 4, '15.01.2000');
        $this->assertEqualsSheetValue($sheet, 4, 4, 'Musterstrasse 25, 70000 Musterstadt');
    
        $properties = $spreadsheet->getProperties();
        $this->assertEquals('Juvem', $properties->getCategory());
        $this->assertEquals($user->fullname(), $properties->getCreator());
        $this->assertEquals($user->fullname(), $properties->getLastModifiedBy());
    }

    protected function assertEqualsSheetValue(Worksheet $sheet, int $columnIndex, int $row, string $expect): void
    {
        $given = $sheet->getCellByColumnAndRow($columnIndex, $row)->getFormattedValue();
        $this->assertEquals($expect, $given);
    }

}
