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

use AppBundle\Entity\Meals\Recipe;
use AppBundle\Entity\Meals\RecipeIngredient;
use AppBundle\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Recipe $recipe */
        $recipe = $options['data'];
        
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'label' => 'Titel'
                ]
            )->add(
                'cookingInstructions',
                TextareaType::class,
                ['label' => 'Zubereitung', 'required' => false, 'empty_data' => '']
            )->add(
                'ingredients',
                CollectionType::class,
                [
                    'label'         => 'Zutaten',
                    'entry_type'    => RecipeIngredientType::class,
                    'entry_options' => [
                        RecipeIngredientType::RECIPE_FIELD => $recipe,
                    ],
                    'allow_add'     => true,
                    'allow_delete'  => true,
                ]
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Recipe::class,
            ]
        );
    }
}
