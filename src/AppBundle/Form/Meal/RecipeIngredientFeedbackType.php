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


use AppBundle\Entity\Meals\Recipe;
use AppBundle\Entity\Meals\RecipeIngredient;
use AppBundle\Entity\Meals\RecipeIngredientFeedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeIngredientFeedbackType extends AbstractType implements DataMapperInterface
{
    const FIELD_RECIPE = 'recipe';
    
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder
            ->add(
                'recipeIngredientId',
                HiddenType::class
                )
            ->add(
                'amountOriginal',
                HiddenType::class
                )
            ->add(
                'unitIdOriginal',
                HiddenType::class
                )
            ->add(
                'ingredientFeedback',
                RecipeItemFeedbackChoiceType::class,
                [
                    'required' => false,
                    'mapped'   => false,
                    'recipe'   => $options['recipe'] ?? null,
                ]
            )->add(
                'amountCorrected',
                NumberType::class,
                [
                    'label'    => 'Neue Kalk. pP.',
                    'required' => false,
                ]
            )
            ->add(
                'unitIdCorrected',
                HiddenType::class,
                [
                    'empty_data' => function (\Symfony\Component\Form\Form $form) {
                        $parent       = $form->getParent();
                        $config       = $parent->getConfig();
                        $ingredientId = $parent->get('recipeIngredientId')->getData();
                        $recipe       = $config->getOption(self::FIELD_RECIPE);
                        /** @var RecipeIngredient $ingredient */
                        $ingredient = $recipe->getIngredient($ingredientId);
                        return $ingredient->getUnit()->getId();
                    },
                ]
            )
            ->setDataMapper($this);
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::FIELD_RECIPE);
        $resolver->setAllowedTypes(self::FIELD_RECIPE, Recipe::class);
        $resolver->setDefaults(
            [
                
                'data_class' => RecipeIngredientFeedback::class,
            ]
        );
    }
    
    /**
     * Maps the view data of a compound form to its children.
     *
     * @param RecipeIngredientFeedback|null $viewData
     * @param array $forms
     */
    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }
        
        if (!$viewData instanceof RecipeIngredientFeedback) {
            throw new UnexpectedTypeException(
                $viewData, RecipeIngredientFeedback::class
            );
        }
        
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);
        
        $forms['recipeIngredientId']->setData($viewData->getRecipeIngredientId());
        $forms['amountOriginal']->setData($viewData->getAmountOriginal());
        $forms['unitIdOriginal']->setData($viewData->getUnitIdOriginal());
        $forms['ingredientFeedback']->setData($viewData->getIngredientFeedback());
        $forms['amountCorrected']->setData($viewData->getAmountCorrected());
        $forms['unitIdCorrected']->setData($viewData->getUnitIdCorrected());
    }
    
    /**
     * Maps the model data of a list of children forms into the view data of their parent.
     *
     * @param FormInterface[]|\Traversable $forms
     * @param mixed $viewData
     */
    public function mapFormsToData($forms, &$viewData)
    {
        /** @var FormInterface[] $forms */
        $forms  = iterator_to_array($forms);
        $config = $forms['ingredientFeedback']->getConfig();
        $recipe = $config->getOption(self::FIELD_RECIPE);
        /** @var RecipeIngredient $ingredient */
        
        
        $recipeIngredientId = $forms['recipeIngredientId']->getData();
        $amountOriginal     = $forms['amountOriginal']->getData();
        $unitIdOriginal     = $forms['unitIdOriginal']->getData();
        $ingredientFeedback = $forms['ingredientFeedback']->getData();
        $amountCorrected    = $forms['amountCorrected']->getData();
        $unitIdCorrected    = $forms['unitIdCorrected']->getData();
        
        $viewData = new RecipeIngredientFeedback(
            $recipeIngredientId,
            $amountOriginal,
            $unitIdOriginal,
            empty($ingredientFeedback) ? null : $ingredientFeedback,
            empty($amountCorrected) ? null : $amountCorrected,
            $unitIdCorrected
        );
    }
    
}