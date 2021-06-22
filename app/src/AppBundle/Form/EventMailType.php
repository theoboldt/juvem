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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EventMailType extends AbstractType
{
    const RECIPIENT_ALL         = 1;
    const RECIPIENT_CONFIRMED   = 2;
    const RECIPIENT_UNCONFIRMED = 3;
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'recipient',
                ChoiceType::class,
                [
                    'label'    => 'Empfänger',
                    'choices'  => [
                        'Alle unbestätigten & bestätigten'       => self::RECIPIENT_ALL,
                        'Alle bestätigten (keine unbestätigten)' => self::RECIPIENT_CONFIRMED,
                        'Alle unbestätigten (keine bestätigten)' => self::RECIPIENT_UNCONFIRMED,
                    ],
                    'required' => true,
                    'attr'     => ['aria-describedby' => 'help-type'],
                ]
            )
            ->add('subject', TextType::class, ['label' => 'Betreff'])
            ->add('title', TextType::class, ['label' => 'Titel'])
            ->add('lead', TextType::class, ['label' => 'Untertitel', 'required' => false])
            ->add(
                'content', TextareaType::class,
                ['label' => 'Hauptinhalt', 'attr' => ['class' => 'markdown-editable preview']]
            );
    }
    
}
