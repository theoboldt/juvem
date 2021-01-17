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
use AppBundle\Export\Customized\CustomizedExport;
use AppBundle\Manager\Payment\PaymentManager;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\Export\TmpDirAccessingTestTrait;
use Tests\JuvemKernelTestCase;

class CustomizedParticipantsExportTest extends JuvemKernelTestCase
{
    use ParticipantTestingDataTrait, TmpDirAccessingTestTrait;

    /**
     * Provide participants
     *
     * @return Participant[]
     */
    private function provideParticipants(): array
    {
        $event          = $this->event();
        $participation1 = $this->participation($event);

        $participant1 = new Participant();
        $participant1->setNameLast('Doe');
        $participant1->setNameFirst('Testchild');
        $participant1->setBirthday(new \DateTime('2000-01-01 10:00:00'));
        $participant1->setFood(new ParticipantFood(4));
        $participant1->setGender(Participant::LABEL_GENDER_FEMALE);
        $participant1->setInfoGeneral('Nothing special to know');
        $participant1->setInfoMedical('No medication needed');
        $participation1->addParticipant($participant1);

        $participant2 = new Participant();
        $participant2->setNameLast('Doe');
        $participant2->setNameFirst('Secondchild');
        $participant2->setBirthday(new \DateTime('2000-01-10 10:00:00'));
        $participant2->setFood(new ParticipantFood(4 + 2 + 8));
        $participant2->setGender(Participant::LABEL_GENDER_MALE);
        $participation1->addParticipant($participant2);

        $participant3 = new Participant();
        $participant3->setNameLast('Doe');
        $participant3->setNameFirst('Thirdchild');
        $participant3->setBirthday(new \DateTime('2000-01-15 10:00:00'));
        $participant3->setFood(new ParticipantFood(2));
        $participant3->setGender(Participant::LABEL_GENDER_MALE);
        $participation1->addParticipant($participant3);

        return [$participant1, $participant2, $participant3];
    }

    public function testDefaultExport(): void
    {
        $kernel = static::bootKernel();
        $kernel->boot();

        $user         = $this->user();
        $participants = $this->provideParticipants();
        $participant  = reset($participants);
        $event        = $participant->getEvent();

        $configuration = [
            'filter'           =>
                [
                    'confirmed'         => 'all',
                    'paid'              => 'all',
                    'rejectedwithdrawn' => 'notrejectedwithdrawn',
                ],
            'participant'      =>
                [
                    'nameFirst'         => true,
                    'nameLast'          => true,
                    'birthday'          => true,
                    'ageAtEvent'        => 'completed',
                    'gender'            => true,
                    'foodVegetarian'    => true,
                    'foodLactoseFree'   => true,
                    'foodLactoseNoPork' => true,
                    'infoMedical'       => true,
                    'infoGeneral'       => true,
                    'basePrice'         => true,
                    'price'             => true,
                    'toPay'             => true,
                    'acquisitionFields' => [],
                    'grouping_sorting'  =>
                        [
                            'grouping' => ['field' => 'nameFirst',],
                            'sorting'  => ['field' => 'aid',],
                        ],
                ],
            'participation'    =>
                [
                    'salutation'        => true,
                    'nameFirst'         => true,
                    'nameLast'          => true,
                    'email'             => true,
                    'phoneNumber'       => 'comma',
                    'addressStreet'     => true,
                    'addressCity'       => true,
                    'addressZip'        => true,
                    'acquisitionFields' =>
                        [
                        ],
                ],
            'title'            => 'Teilnehmer',
            'additional_sheet' => [],
        ];
        $export        = new CustomizedExport(
            $this->customization(),
            $kernel->getContainer()->get(PaymentManager::class),
            $event,
            $participants,
            $user,
            $configuration
        );
        $export->setMetadata();

        $export->process();

        $tmpPath       = __DIR__ . '/../../../../var/tmp/' . uniqid('export_test');
        self::$files[] = $tmpPath;
        $export->write($tmpPath);

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet       = $spreadsheet->getSheet(0);
        
        $this->assertEquals('Teilnehmer', $sheet->getTitle());

        $this->assertEqualsSheetValue($sheet, 1, 2, 'Testchild');
        $this->assertEqualsSheetValue($sheet, 2, 2, 'Doe');
        $this->assertEqualsSheetValue($sheet, 3, 2, '01.01.2000');
        $this->assertEqualsSheetValue($sheet, 4, 2, '10');
        $this->assertEqualsSheetValue($sheet, 5, 2, 'w');
        $this->assertEqualsSheetValue($sheet, 6, 2, 'nein');
        $this->assertEqualsSheetValue($sheet, 7, 2, 'nein');
        $this->assertEqualsSheetValue($sheet, 8, 2, 'os');
        $this->assertEqualsSheetValue($sheet, 9, 2, 'No medication needed');
        $this->assertEqualsSheetValue($sheet, 10, 2, 'Nothing special to know');
        $this->assertEqualsSheetValue($sheet, 11, 2, 'Ms.');
        $this->assertEqualsSheetValue($sheet, 12, 2, 'Maria');
        $this->assertEqualsSheetValue($sheet, 13, 2, 'Doe');
        $this->assertEqualsSheetValue($sheet, 14, 2, 'Musterstrasse 25');
        $this->assertEqualsSheetValue($sheet, 15, 2, 'Musterstadt');
        $this->assertEqualsSheetValue($sheet, 16, 2, '70000');
        $this->assertEqualsSheetValue($sheet, 17, 2, 'doe+example@example.com');
        $this->assertEqualsSheetValue($sheet, 18, 2, '0163000000');

        $this->assertEqualsSheetValue($sheet, 1, 3, 'Secondchild');
        $this->assertEqualsSheetValue($sheet, 2, 3, 'Doe');
        $this->assertEqualsSheetValue($sheet, 3, 3, '10.01.2000');
        $this->assertEqualsSheetValue($sheet, 4, 3, '9');
        $this->assertEqualsSheetValue($sheet, 5, 3, 'm');
        $this->assertEqualsSheetValue($sheet, 6, 3, 'vs');
        $this->assertEqualsSheetValue($sheet, 7, 3, 'lf');
        $this->assertEqualsSheetValue($sheet, 8, 3, 'os');
        $this->assertEqualsSheetValue($sheet, 9, 3, '');
        $this->assertEqualsSheetValue($sheet, 10, 3, '');
        $this->assertEqualsSheetValue($sheet, 11, 3, 'Ms.');
        $this->assertEqualsSheetValue($sheet, 12, 3, 'Maria');
        $this->assertEqualsSheetValue($sheet, 13, 3, 'Doe');
        $this->assertEqualsSheetValue($sheet, 14, 3, 'Musterstrasse 25');
        $this->assertEqualsSheetValue($sheet, 15, 3, 'Musterstadt');
        $this->assertEqualsSheetValue($sheet, 16, 3, '70000');
        $this->assertEqualsSheetValue($sheet, 17, 3, 'doe+example@example.com');
        $this->assertEqualsSheetValue($sheet, 18, 3, '0163000000');

        $this->assertEqualsSheetValue($sheet, 1, 4, 'Thirdchild');
        $this->assertEqualsSheetValue($sheet, 2, 4, 'Doe');
        $this->assertEqualsSheetValue($sheet, 3, 4, '15.01.2000');
        $this->assertEqualsSheetValue($sheet, 4, 4, '9');
        $this->assertEqualsSheetValue($sheet, 5, 4, 'm');
        $this->assertEqualsSheetValue($sheet, 6, 4, 'vs');
        $this->assertEqualsSheetValue($sheet, 7, 4, 'nein');
        $this->assertEqualsSheetValue($sheet, 8, 4, 'nein');
        $this->assertEqualsSheetValue($sheet, 9, 4, '');
        $this->assertEqualsSheetValue($sheet, 10, 4, '');
        $this->assertEqualsSheetValue($sheet, 11, 4, 'Ms.');
        $this->assertEqualsSheetValue($sheet, 12, 4, 'Maria');
        $this->assertEqualsSheetValue($sheet, 13, 4, 'Doe');
        $this->assertEqualsSheetValue($sheet, 14, 4, 'Musterstrasse 25');
        $this->assertEqualsSheetValue($sheet, 15, 4, 'Musterstadt');
        $this->assertEqualsSheetValue($sheet, 16, 4, '70000');
        $this->assertEqualsSheetValue($sheet, 17, 4, 'doe+example@example.com');
        $this->assertEqualsSheetValue($sheet, 18, 4, '0163000000');

        $properties = $spreadsheet->getProperties();
        $this->assertEquals('Juvem', $properties->getCategory());
        $this->assertEquals($user->fullname(), $properties->getCreator());
        $this->assertEquals($user->fullname(), $properties->getLastModifiedBy());
    }

    public function testParticipationSheet(): void
    {
        $kernel = static::bootKernel();
        $kernel->boot();

        $user         = $this->user();
        $participants = $this->provideParticipants();
        $participant  = reset($participants);
        $event        = $participant->getEvent();

        $configuration = [
            'filter'           =>
                [
                    'confirmed'         => 'all',
                    'paid'              => 'all',
                    'rejectedwithdrawn' => 'notrejectedwithdrawn',
                ],
            'participant'      =>
                [
                    'nameFirst'         => true,
                    'nameLast'          => true,
                    'birthday'          => true,
                    'ageAtEvent'        => 'completed',
                    'gender'            => true,
                    'foodVegetarian'    => true,
                    'foodLactoseFree'   => true,
                    'foodLactoseNoPork' => true,
                    'infoMedical'       => true,
                    'infoGeneral'       => true,
                    'basePrice'         => true,
                    'price'             => true,
                    'toPay'             => true,
                    'acquisitionFields' => [],
                    'grouping_sorting'  =>
                        [
                            'grouping' => ['field' => 'nameFirst',],
                            'sorting'  => ['field' => 'aid',],
                        ],
                ],
            'participation'    =>
                [
                    'salutation'        => true,
                    'nameFirst'         => true,
                    'nameLast'          => true,
                    'email'             => true,
                    'phoneNumber'       => 'comma',
                    'addressStreet'     => true,
                    'addressCity'       => true,
                    'addressZip'        => true,
                    'acquisitionFields' =>
                        [
                        ],
                ],
            'title'            => 'Teilnehmer',
            'additional_sheet' => [
                'participation' => true,
            ],
        ];
        $export        = new CustomizedExport(
            $this->customization(),
            $kernel->getContainer()->get(PaymentManager::class),
            $event,
            $participants,
            $user,
            $configuration
        );
        $export->setMetadata();
        $export->process();

        $tmpPath       = __DIR__ . '/../../../../var/tmp/' . uniqid('export_test');
        self::$files[] = $tmpPath;
        $export->write($tmpPath);

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet       = $spreadsheet->getSheet(1);
        $this->assertEquals('Anmeldungen', $sheet->getTitle());
        

        $this->assertEqualsSheetValue($sheet, 1, 2, 'Ms.');
        $this->assertEqualsSheetValue($sheet, 2, 2, 'Maria');
        $this->assertEqualsSheetValue($sheet, 3, 2, 'Doe');
        $this->assertEqualsSheetValue($sheet, 4, 2, 'Musterstrasse 25');
        $this->assertEqualsSheetValue($sheet, 5, 2, 'Musterstadt');
        $this->assertEqualsSheetValue($sheet, 6, 2, '70000');
        $this->assertEqualsSheetValue($sheet, 7, 2, 'doe+example@example.com');
        $this->assertEqualsSheetValue($sheet, 8, 2, '0163000000 (Mobile)');
        
        $this->assertEqualsSheetValue($sheet, 10, 2, '3');
    }
    
    public function testSubventionSheet(): void
    {
        $kernel = static::bootKernel();
        $kernel->boot();

        $user         = $this->user();
        $participants = $this->provideParticipants();
        $participant  = reset($participants);
        $event        = $participant->getEvent();

        $configuration = [
            'filter'           =>
                [
                    'confirmed'         => 'all',
                    'paid'              => 'all',
                    'rejectedwithdrawn' => 'notrejectedwithdrawn',
                ],
            'participant'      =>
                [
                    'nameFirst'         => true,
                    'nameLast'          => true,
                    'birthday'          => true,
                    'ageAtEvent'        => 'completed',
                    'gender'            => true,
                    'foodVegetarian'    => true,
                    'foodLactoseFree'   => true,
                    'foodLactoseNoPork' => true,
                    'infoMedical'       => true,
                    'infoGeneral'       => true,
                    'basePrice'         => true,
                    'price'             => true,
                    'toPay'             => true,
                    'acquisitionFields' => [],
                    'grouping_sorting'  =>
                        [
                            'grouping' => ['field' => 'nameFirst',],
                            'sorting'  => ['field' => 'aid',],
                        ],
                ],
            'participation'    =>
                [
                    'salutation'        => true,
                    'nameFirst'         => true,
                    'nameLast'          => true,
                    'email'             => true,
                    'phoneNumber'       => 'comma',
                    'addressStreet'     => true,
                    'addressCity'       => true,
                    'addressZip'        => true,
                    'acquisitionFields' =>
                        [
                        ],
                ],
            'title'            => 'Teilnehmer',
            'additional_sheet' => [
                'subvention_request' => true,
            ],
        ];
        $export        = new CustomizedExport(
            $this->customization(),
            $kernel->getContainer()->get(PaymentManager::class),
            $event,
            $participants,
            $user,
            $configuration
        );
        $export->setMetadata();
        $export->process();

        $tmpPath       = __DIR__ . '/../../../../var/tmp/' . uniqid('export_test');
        self::$files[] = $tmpPath;
        $export->write($tmpPath);

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet       = $spreadsheet->getSheet(1);
        $this->assertEquals('Teilnehmer - Zuschussantrag', $sheet->getTitle());

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
    }

    protected function assertEqualsSheetValue(Worksheet $sheet, int $columnIndex, int $row, string $expect): void
    {
        $given = $sheet->getCellByColumnAndRow($columnIndex, $row)->getFormattedValue();
        $this->assertEquals($expect, $given);
    }

}
