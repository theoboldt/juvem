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


use AppBundle\Entity\Meals\RecipeFeedback;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeItemFeedbackChoiceType extends ChoiceType
{
    
    const AMOUNT_NULL       = '';
    const AMOUNT_NULL_LABEL = 'keine Angabe';
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [
            RecipeFeedback::AMOUNT_WAY_TOO_LESS_LABEL => RecipeFeedback::AMOUNT_WAY_TOO_LESS,
            RecipeFeedback::AMOUNT_TOO_LESS_LABEL     => RecipeFeedback::AMOUNT_TOO_LESS,
            RecipeFeedback::AMOUNT_OK_LABEL           => RecipeFeedback::AMOUNT_OK,
            RecipeFeedback::AMOUNT_TOO_MUCH_LABEL     => RecipeFeedback::AMOUNT_TOO_MUCH,
            RecipeFeedback::AMOUNT_WAY_TOO_MUCH_LABEL => RecipeFeedback::AMOUNT_WAY_TOO_MUCH,
        ];
        if (!$options['required']) {
            $choices[self::AMOUNT_NULL_LABEL] = self::AMOUNT_NULL;
        }
        $options['choices']    = $choices;
        $options['empty_data'] = $options['required'] ? RecipeFeedback::AMOUNT_OK : self::AMOUNT_NULL;
        
        parent::buildForm($builder, $options);
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'block_name'  => 'feedback_choice',
                'expanded'    => true,
                'multiple'    => false,
                'choice_attr' => function ($choice, $key, $value) {
                    if ($choice === self::AMOUNT_NULL) {
                        $title = 'Keine Angabe für diese Zutat';
                        $short = 'kA';
                    } else {
                        switch ($choice) {
                            case RecipeFeedback::AMOUNT_WAY_TOO_LESS:
                                $title = 'Die Menge war deutlich zu wenig';
                                $short = '++';
                                break;
                            case RecipeFeedback::AMOUNT_TOO_LESS:
                                $title = 'Die Menge war eher zu wenig';
                                $short = '+';
                                break;
                            case RecipeFeedback::AMOUNT_OK:
                                $title = 'Die Menge war angemessen';
                                $short = 'ok';
                                break;
                            case RecipeFeedback::AMOUNT_TOO_MUCH:
                                $title = 'Die Menge war eher zu viel';
                                $short = '-';
                                break;
                            case RecipeFeedback::AMOUNT_WAY_TOO_MUCH:
                                $title = 'Die Menge war deutlich zu viel';
                                $short = '--';
                                break;
                            default:
                                $title = $key;
                                $short = $value;
                                break;
                        }
                    }
                    
                    return ['title' => $title, 'short' => $short];
                },
            ]
        );
    }
    
}