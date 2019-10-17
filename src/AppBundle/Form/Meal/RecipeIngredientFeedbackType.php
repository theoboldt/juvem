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


use AppBundle\Entity\Meals\RecipeIngredientFeedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeIngredientFeedbackType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'feedback',
                RecipeItemFeedbackChoiceType::class,
                [
                    'label'    => 'Mengen-Bewertung',
                    'required' => false,
                ]
            )->add(
                'correctedAmount',
                NumberType::class,
                [
                    'label'      => 'Neue Kalk. pP.',
                    'required'   => false,
                ]
            );
        
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => RecipeIngredientFeedback::class,
            ]
        );
    }
    
}