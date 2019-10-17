<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\Meal;

use AppBundle\Entity\Meals\FoodProperty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FoodPropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label'    => 'Name',
                    'required' => true,
                ]
            )
            ->add(
                'exclusionTerm',
                TextType::class,
                [
                    'label'    => 'Ausschluss-Begriff',
                    'required' => false,
                ]
            )
            ->add(
                'exclusionTermDescription',
                TextType::class,
                [
                    'label'    => 'Beschreibung des Ausschluss-Begriff',
                    'required' => false,
                ]
            )
            ->add(
                'exclusionTermShort',
                TextType::class,
                [
                    'label'    => 'Abkürzung des Ausschluss-Begriffs',
                    'required' => false,
                ]
            
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => FoodProperty::class,
            ]
        );
    }
}
