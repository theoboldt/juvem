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
use AppBundle\Entity\Meals\QuantityUnit;
use AppBundle\Entity\Meals\Viand;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ViandType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder->add(
            'name',
            TextType::class,
            [
                'label'    => 'Name',
                'required' => true,
            ]
        )->add(
            'defaultUnit',
            EntityType::class,
            [
                'label'        => 'Standard-Einheit',
                'class'        => QuantityUnit::class,
                'choice_label' => 'nameAndShort',
                'multiple'     => false,
                'required'     => false
            ]
        )->add(
            'properties',
            EntityType::class,
            [
                'label'        => 'Eigenschaften',
                'class'        => FoodProperty::class,
                'choice_label' => 'name',
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false
            ]
        
        );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Viand::class,
            ]
        );
    }
}
