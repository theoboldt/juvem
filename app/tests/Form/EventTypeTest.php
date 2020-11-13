<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Form;


use AppBundle\Entity\Event;
use AppBundle\Form\EventType;
use Tests\JuvemKernelTestCase;

class EventTypeTest extends JuvemKernelTestCase
{

    public function testSubmitValidData()
    {
        $formData = [
            'title'                   => 'Camping Trip',
            'description'             => 'Great camping trip',
            "startDate"               => "2000-01-01",
            "hasStartTime"            => "1",
            "startTime"               => [
                'hour'   => '10',
                'minute' => '30',
            ],
            "hasEndDate"              => "0",
            "endDate"                 => "",
            "hasEndTime"              => "1",
            "endTime"                 => [
                'hour'   => '20',
                'minute' => '45',
            ],
            "isVisible"               => "0",
            "isActive"                => "1",
            "isAutoConfirm"           => "0",
            "price"                   => "25",
            "ageRange"                => "5-15",
            "hasWaitingListThreshold" => "1",
            "waitingListThreshold"    => "30",
            "linkTitle"               => "Github Link",
            "linkUrl"                 => "https://github.com/theoboldt/juvem",
            "addressTitle"            => "Testing",
            "addressStreet"           => "Test Street 10",
            "addressZip"              => "7000",
            "addressCity"             => "Testcity",
            "addressCountry"          => "Deutschland",
            "isShowAddress"           => "1",
            "isShowMap"               => "1",
            "isShowWeather"           => "1",
            "acquisitionAttributes"   => [],
        ];

        $kernel = static::bootKernel();
        $kernel->boot();
        $factory = $kernel->getContainer()->get('form.factory');

        $model = new Event();
        $form  = $factory->create(EventType::class, $model, ['csrf_protection' => false]);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $errors = iterator_to_array($form->getErrors(true, true));
        $this->assertCount(0, $errors);

        $this->assertEquals('Camping Trip', $model->getTitle());
        $this->assertEquals('Great camping trip', $model->getDescription());
        $this->assertEquals('Great camping trip', $model->getDescriptionMeta(true));
        $this->assertEquals(new \DateTime('2000-01-01 00:00:00'), $model->getStartDate());
        $this->assertEquals(new \DateTime('1970-01-01 10:30:00'), $model->getStartTime());
        $this->assertNull($model->getEndDate());
        $this->assertEquals(new \DateTime('1970-01-01 20:45:00'), $model->getEndTime());
        $this->assertEquals('5-15', $model->getAgeRange());
        $this->assertEquals(25, (int)$model->getPrice(true));
        $this->assertEquals('Testing', $model->getAddressTitle());
        $this->assertEquals('Test Street 10', $model->getAddressStreet());
        $this->assertEquals('Testcity', $model->getAddressCity());
        $this->assertEquals('7000', $model->getAddressZip());
        $this->assertEquals('Deutschland', $model->getAddressCountry());
        $this->assertEquals(30, $model->getWaitingListThreshold());
        $this->assertEquals('Github Link', $model->getLinkTitle());
        $this->assertEquals('https://github.com/theoboldt/juvem', $model->getLinkUrl());
        $this->assertTrue($model->hasLink());
        $this->assertTrue($model->isActive());
        $this->assertFalse($model->isVisible());
        $this->assertEquals([], $model->getParticipations());
        $this->assertEquals([], $model->getGalleryImages());
        $this->assertFalse($model->isGalleryLinkSharing());
    }

}
