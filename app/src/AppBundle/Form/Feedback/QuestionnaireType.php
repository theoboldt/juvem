<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\Feedback;

use AppBundle\Feedback\FeedbackQuestionnaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionnaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'feedbackDaysAfterEvent',
                IntegerType::class,
                [
                    'label'    => 'Automatische Feedback-Email',
                    'required' => true,
                    'attr'     => ['aria-describedby' => 'help-feedback-days'],
                ]
            )
            ->add(
                'introductionEmail',
                TextareaType::class,
                [
                    'label'    => 'Text-Ergänzung in Hinweis-Email',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-introduction-email', 'class' => 'markdown-editable'],
                ]
            )->add(
                'introductionQuestionnaire',
                TextareaType::class,
                [
                    'label'    => 'Hinweise beim Fragebogen',
                    'required' => false,
                    'attr'     => ['aria-describedby' => 'help-introduction-questionnaire',
                                   'class'            => 'markdown-editable',
                    ],
                ]

            )->add(
                'questions',
                CollectionType::class,
                [
                    'label'        => 'Fragen',
                    'entry_type'   => QuestionType::class,
                    'by_reference' => true,
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'required'     => true,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => FeedbackQuestionnaire::class,
            ]
        );
    }
}
