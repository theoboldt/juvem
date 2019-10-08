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

use AppBundle\Entity\Meals\QuantityUnit;
use AppBundle\Entity\Meals\Recipe;
use AppBundle\Entity\Meals\RecipeIngredient;
use AppBundle\Entity\Meals\Viand;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeIngredientType extends AbstractType
{
    
    const RECIPE_FIELD = 'recipe';
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'amount',
                NumberType::class,
                [
                    'label'      => 'Menge pro Person',
                    'required'   => false,
                    'empty_data' => 0
                ]
            )
            ->add(
                'description',
                TextType::class,
                [
                    'label'      => 'Hinweise',
                    'required'   => false,
                    'empty_data' => ''
                ]
            )
            ->add(
                'unit',
                EntityType::class,
                [
                    'label'        => 'Einheit',
                    'class'        => QuantityUnit::class,
                    'choice_label' => 'nameAndShort',
                    'multiple'     => false,
                    'required'     => true,
                ]
            )
            ->add(
                'viand',
                EntityType::class,
                [
                    'class'        => Viand::class,
                    'choice_label' => 'name',
                    'multiple'     => false,
                    'expanded'     => false,
                    'label'        => 'Lebensmittel',
                ]
            
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::RECIPE_FIELD);
        $resolver->setAllowedTypes(self::RECIPE_FIELD, Recipe::class);
        
        $resolver->setDefaults(
            [
                'data_class' => RecipeIngredient::class,
                'empty_data' => function (FormInterface $form) {
                    $recipe = $form->getConfig()->getOption(RecipeIngredientType::RECIPE_FIELD);
                    $use    = new RecipeIngredient($recipe);
                    return $use;
                },
            ]
        );
    }
}
