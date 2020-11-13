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


use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Export\ParticipationsExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParticipationsExportTest extends ExportTestCase
{

    public function testParticipationsExport(): void
    {
        $event = $this->event();

        $participation1 = new Participation($event, true, true);
        $participation1->setSalutation('Ms.');
        $participation1->setNameLast('Doe');
        $participation1->setNameFirst('Maria');
        $participation1->setAddressStreet('Musterstrasse 25');
        $participation1->setAddressZip('70000');
        $participation1->setAddressCity('Musterstadt');
        $participation1->setEmail('doe+example@example.com');
        $participation1Number = new \libphonenumber\PhoneNumber();
        $participation1Number->setNationalNumber('0163000000');
        $participation1Number->setCountryCode(49);
        $participation1->addPhoneNumber(new PhoneNumber($participation1Number, 'Mobile'));

        $participations = [
            $participation1,
        ];

        $export = new ParticipationsExport(
            $this->customization(), $event, $participations, $this->user()
        );
        $export->setMetadata();
        $export->process();

        $tmpPath       = __DIR__ . '/../../../var/tmp/' . uniqid('export_test');
        self::$files[] = $tmpPath;
        $export->write($tmpPath);

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet       = $spreadsheet->getSheet(0);
        $this->assertEqualsSheetValue($sheet, 1, 2, 'Ms.');
        $this->assertEqualsSheetValue($sheet, 2, 2, 'Maria');
        $this->assertEqualsSheetValue($sheet, 3, 2, 'Doe');
        $this->assertEqualsSheetValue($sheet, 4, 2, 'Musterstrasse 25');
        $this->assertEqualsSheetValue($sheet, 5, 2, 'Musterstadt');
        $this->assertEqualsSheetValue($sheet, 6, 2, '70000');
        $this->assertEqualsSheetValue($sheet, 7, 2, 'doe+example@example.com');
        $this->assertEqualsSheetValue($sheet, 8, 2, '0163000000 (Mobile)');
        $this->assertEqualsSheetValue($sheet, 10, 2, '0');
    }

    protected function assertEqualsSheetValue(Worksheet $sheet, int $columnIndex, int $row, string $expect): void
    {
        $given = $sheet->getCellByColumnAndRow($columnIndex, $row)->getValue();
        $this->assertEquals($expect, $given);
    }

}
