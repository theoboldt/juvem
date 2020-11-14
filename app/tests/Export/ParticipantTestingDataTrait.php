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


use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Entity\User;
use AppBundle\Twig\GlobalCustomizationConfiguration;
use AppBundle\Twig\GlobalCustomizationConfigurationProvider;

trait ParticipantTestingDataTrait
{

    /**
     * @var array|string[]
     */
    protected static array $files = [];

    protected static function ensureTmpDirAccessible(): void
    {
        $tmpDir = __DIR__ . '/../../../var/tmp';
        if (!file_exists($tmpDir)) {
            $umask = umask();
            umask(0);
            if (!mkdir($tmpDir, 0777, true)) {
                umask($umask);
                throw new \RuntimeException('Precondition failed: Tmp dir inaccessible');
            }
            umask($umask);
        }
    }

    protected static function removeTmpFiles(): void
    {
        foreach (self::$files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        self::$files = [];
    }

    /**
     * Create employee
     *
     * @param Event $event
     * @return Employee
     */
    protected function employee(Event $event): Employee
    {
        $employee = new Employee($event);
        $employee->setNameFirst('Max');
        $employee->setNameLast('Dix');
        $employee->setSalutation('Herr');
        $employee->setEmail('dix+example@example.com');
        $employee->setAddressStreet('Musterstrasse 25');
        $employee->setAddressZip('70000');
        $employee->setAddressCity('Musterstadt');
        $number = new \libphonenumber\PhoneNumber();
        $number->setNationalNumber('0164000000');
        $number->setCountryCode(49);
        $employee->addPhoneNumber(new PhoneNumber($number, 'Mobile Number'));

        return $employee;
    }

    /**
     * Create participation
     *
     * @param Event $event
     * @return Participation
     */
    protected function participation(Event $event): Participation
    {
        $participation = new Participation($event, true, true);
        $participation->setSalutation('Ms.');
        $participation->setNameLast('Doe');
        $participation->setNameFirst('Maria');
        $participation->setAddressStreet('Musterstrasse 25');
        $participation->setAddressZip('70000');
        $participation->setAddressCity('Musterstadt');
        $participation->setEmail('doe+example@example.com');
        $participationNumber = new \libphonenumber\PhoneNumber();
        $participationNumber->setNationalNumber('0163000000');
        $participationNumber->setCountryCode(49);
        $participation->addPhoneNumber(new PhoneNumber($participationNumber, 'Mobile'));

        return $participation;
    }

    /**
     * @return GlobalCustomizationConfigurationProvider
     */
    protected function customization(): GlobalCustomizationConfigurationProvider
    {
        return new GlobalCustomizationConfiguration(
            'Juvem Testorganisation',
            'Teststrasse 10',
            '7000',
            'Teststadt',
            '',
            '',
            '',
            '',
        );
    }

    /**
     * @return Event
     */
    protected function event(): Event
    {
        $event = new Event();
        $event->setTitle('Camping Trip');
        $event->setStartDate(new \DateTime('2010-01-01 10:00:00'));

        return $event;
    }

    /**
     * @return User
     */
    protected function user(): User
    {
        $user = new User();
        $user->setNameFirst('Max');
        $user->setNameLast('Muster');
        return $user;
    }
}
