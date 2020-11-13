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
use Symfony\Component\Form\Test\TypeTestCase;

class EventTypeTest extends TypeTestCase
{

    public function testSubmitValidData()
    {
        $formData = [
            'title'       => 'Camping Trip',
            'description' => 'Great camping trip',
            'startDate'
        ];

        $model = new Event();
        $form  = $this->factory->create(EventType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('Camping Trip', $model->getTitle());
        $this->assertEquals('Great camping trip', $model->getDescription());
        $this->assertEquals('Great camping trip', $model->getDescriptionMeta(true));
        
    }

}
