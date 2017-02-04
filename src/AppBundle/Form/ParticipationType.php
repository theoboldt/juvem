<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ParticipationType extends ParticipationBaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'acceptPrivacy',
            CheckboxType::class,
            [
                'label'       => 'Ich habe die Datenschutzerklärung gelesen und erkläre mich mit den Angaben einverstanden. Ich kann diese Erklärung jederzeit Wiederrufen.',
                'required'    => true,
                'constraints' => new NotBlank(['message' => 'Sie müssen sich mit der Datenschutzerklärung einverstanden erklären, um die Anmeldung abgeben zu können. In einzelnen Ausnahmefällen können Anmeldungen ohne Verwendung des Anmeldesystems abgegeben werden. Wenden Sie sich dazu bitte telefonisch oder per E-Mail an uns.']),
                'mapped'      => false,
            ]
        );

        $builder->add(
            'acceptConditionsOfTravel',
            CheckboxType::class,
            [
                'label'       => 'Ich akzeptiere die Reisebedingungen und erkläre mich mit den Angaben einverstanden.',
                'required'    => true,
                'constraints' => new NotBlank(['message' => 'Sie müssen sich mit der Reisebedingungen einverstanden erklären, um die Anmeldung abgeben zu können. Bei Fragen können Sie sich telefonisch oder per E-Mail an uns wenden.']),
                'mapped'      => false
            ]
        );
    }
}
