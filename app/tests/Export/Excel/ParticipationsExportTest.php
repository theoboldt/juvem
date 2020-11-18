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


use AppBundle\Export\ParticipationsExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParticipationsExportTest extends ExportTestCase
{

    public function testParticipationsExport(): void
    {
        $user  = $this->user();
        $event = $this->event();

        $participation1 = $this->participation($event);

        $participations = [
            $participation1,
        ];

        $export = new ParticipationsExport(
            $this->customization(), $event, $participations, $user
        );
        $export->setMetadata();
        $export->process();

        $tmpPath       = __DIR__ . '/../../../../var/tmp/' . uniqid('export_test');
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

        $properties = $spreadsheet->getProperties();
        $this->assertEquals('Juvem', $properties->getCategory());
        $this->assertEquals($user->fullname(), $properties->getCreator());
        $this->assertEquals($user->fullname(), $properties->getLastModifiedBy());
    }

    protected function assertEqualsSheetValue(Worksheet $sheet, int $columnIndex, int $row, string $expect): void
    {
        $given = $sheet->getCellByColumnAndRow($columnIndex, $row)->getValue();
        $this->assertEquals($expect, $given);
    }

}
