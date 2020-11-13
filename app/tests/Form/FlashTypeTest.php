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


use AppBundle\Entity\Flash;
use AppBundle\Form\FlashType;
use Symfony\Component\Form\Test\TypeTestCase;

class FlashTypeTest extends TypeTestCase
{
    
    public function provideMessageTypes(): array
    {
        return [
            ['success'],
            ['info'],
            ['warning'],
            ['danger'],
        ];
    }
    
    /**
     * @dataProvider provideMessageTypes
     * @param string $messageType
     */
    public function testSubmitValidData(string $messageType)
    {
        $formData = [
            'message' => 'Test flash message',
            'type'    => $messageType,
        ];
        
        $model = new Flash();
        $form  = $this->factory->create(FlashType::class, $model);
        
        $form->submit($formData);
        
        $this->assertTrue($form->isSynchronized());
        
        $this->assertEquals('Test flash message', $model->getMessage());
        $this->assertEquals($messageType, $model->getType());
    }
    
    
    
}