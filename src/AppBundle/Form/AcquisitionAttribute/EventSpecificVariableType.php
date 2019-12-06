<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\AcquisitionAttribute;

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventSpecificVariableType extends AbstractType
{
    const FIELD_ATTRIBUTE = 'attribute';
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder
            ->add(
                'description',
                TextType::class,
                [
                    'label'    => 'Beschreibung',
                    'required' => true,
                ]
            )
            ->add(
                'defaultValue',
                NumberType::class,
                [
                    'label'    => 'Standardwert',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-default-value'],
                ]
            
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::FIELD_ATTRIBUTE);
        $resolver->setAllowedTypes(self::FIELD_ATTRIBUTE, Attribute::class);
        $resolver->setDefaults(
            [
                'empty_data' => function (FormInterface $form) {
                    $attribute = $form->getConfig()->getOption(self::FIELD_ATTRIBUTE);
                    return new EventSpecificVariable($attribute);
                },
                'data_class' => EventSpecificVariable::class,
            ]
        );
    }
}
