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
        $event->setDescription('Funny camping trip');
        $event->setStartDate(new \DateTime('2010-01-01 10:00:00'));
        $event->setIsActive(false);
        $event->setIsVisible(false);
        $event->setIsActiveRegistrationEmployee(false);
        $event->setIsAutoConfirm(false);
        $event->setIsCalendarEntryEnabled(false);
        $event->setIsFeedbackQuestionnaireEnabled(false);
        $event->setIsFeedbackQuestionnaireSent(false);
        $event->setIsGalleryLinkSharing(false);
        $event->setIsShowAddress(false);
        $event->setIsShowMap(false);
        $event->setIsShowWeather(false);

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
