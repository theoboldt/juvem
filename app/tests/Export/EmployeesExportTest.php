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


use AppBundle\Export\EmployeesExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExportTest extends ExportTestCase
{

    public function testParticipationsExport(): void
    {
        $user  = $this->user();
        $event = $this->event();

        $employees = [$this->employee($event)];

        $export = new EmployeesExport(
            $this->customization(), $event, $employees, $user
        );
        $export->setMetadata();
        $export->process();

        $tmpPath       = __DIR__ . '/../../../var/tmp/' . uniqid('export_test');
        self::$files[] = $tmpPath;
        $export->write($tmpPath);

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet       = $spreadsheet->getSheet(0);
        $this->assertEqualsSheetValue($sheet, 1, 2, 'Max');
        $this->assertEqualsSheetValue($sheet, 2, 2, 'Dix');
        $this->assertEqualsSheetValue($sheet, 3, 2, 'Musterstrasse 25, 70000 Musterstadt');
        $this->assertEqualsSheetValue($sheet, 4, 2, '0164000000');

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
