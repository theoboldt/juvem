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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserRegistrationFormType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options = [])
    {
        parent::buildForm($builder, $options);
        $builder
            ->add(
                'nameFirst',
                TextType::class,
                [
                    'label'    => 'Vorname',
                    'required' => false
                ]
            )
            ->add(
                'stringCount',
                NumberType::class,
                [
                    'mapped'      => false,
                    'required'    => true,
                    'label'       => 'Saiten-Zahl',
                    'constraints' => [
                        new NotBlank(
                            [
                                'message' => 'Bitte zählen Sie Saiten im Bild und geben Sie deren Anzahl in das Eingabefeld ein.'
                            
                            ]
                        
                        ),
                        new EqualTo(
                            [
                                'value'   => 6,
                                'message' => 'Bitte zählen Sie die sechs Saiten erneut und geben Sie deren Anzahl nummerisch in das Eingabefeld ein.'
                            ]
                        )
                    ]
                ]
            )
            ->add(
                'nameLast',
                TextType::class,
                ['label' => 'Nachname']
            )
            ->remove('username');  // we use email as the username
    }
    
    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }
    
    public function getBlockPrefix()
    {
        return 'app_bundle_user_registration';
    }
    
}
