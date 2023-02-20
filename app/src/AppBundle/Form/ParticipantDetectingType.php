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


use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ParticipantDetectingType
 * 
 * Can not be moved in code right now because class name is used in database 
 */
class ParticipantDetectingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('class', 'participant-detecting');
        $builder
            ->add(
                'relatedFirstName',
                TextType::class,
                [
                    'label'    => 'Vorname (verknüpfte Teilnehmer:in)',
                    'required' => false,
                    'attr'     => ['class' => 'col-sm-6 participant-detecting-firstname'],
                ]
            )
            ->add(
                'relatedLastName',
                TextType::class,
                [
                    'label'    => 'Nachname (verknüpfte Teilnehmer:in)',
                    'required' => false,
                    'attr'     => ['class' => 'col-sm-6 participant-detecting-lastname'],
                ]

            );
    }
    
    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', ParticipantDetectingCustomFieldValue::class);
    }
    
}
