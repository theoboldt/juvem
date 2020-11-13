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


use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\PhoneNumberType;
use Symfony\Component\Form\Test\TypeTestCase;

class PhoneNumberTypeTest extends TypeTestCase
{

    public function testSubmitValidData()
    {
        $formData = [
            'number'      => '+4916000000',
            'description' => 'Mobile Phone',
        ];

        $model = new PhoneNumber();
        $form  = $this->factory->create(PhoneNumberType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());


        $number = $model->getNumber();
        $this->assertInstanceOf(\libphonenumber\PhoneNumber::class, $number);
        $this->assertEquals(49, $number->getCountryCode());
        $this->assertEquals('16000000', $number->getNationalNumber());

        $this->assertEquals('Mobile Phone', $model->getDescription());
    }

}
