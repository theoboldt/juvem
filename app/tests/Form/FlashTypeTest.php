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
    
    public function testSubmitValidityDates()
    {
        $formData = [
            'message'      => 'Test flash message',
            'type'         => 'success',
            'hasValidFrom' => true,
            'validFrom'    => [
                'date' => [
                    'year'  => (string)date('Y'),
                    'month' => '1',
                    'day'   => '1'
                ],
                'time' => [
                    'hour'   => '10',
                    'minute' => '0',
                ]
            ],
        ];
        
        $model = new Flash();
        $form  = $this->factory->create(FlashType::class, $model);
        
        $form->submit($formData);
        
        $this->assertTrue($form->isSynchronized());
        $this->assertNotNull($model->getValidFrom());
        $this->assertEquals(new \DateTime(date('Y') . '-01-01 10:00:00'), $model->getValidFrom());
    }
    
    public function testSubmitInvalidDate()
    {
        $formData = [
            'message'      => 'Test flash message',
            'type'         => 'success',
            'hasValidFrom' => true,
            'validFrom'    => [
                'date' => [
                    'year'  => (string)(date('Y') - 2),
                    'month' => '1',
                    'day'   => '1'
                ],
                'time' => [
                    'hour'   => '10',
                    'minute' => '0',
                ]
            ],
        ];
        
        $model = new Flash();
        $form  = $this->factory->create(FlashType::class, $model);
        
        $form->submit($formData);
        
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $errors = iterator_to_array($form->getErrors(true, true));
        $this->assertCount(1, $errors);
        $error = $errors[0];
        
        $this->assertEquals('The value ' . (string)(date('Y') - 2) . ' is not valid.', $error->getMessage());
    }
    
    
}