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
use AppBundle\Feedback\FeedbackQuestionnaireFillout;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionnaireFilloutType extends AbstractType
{
    const QUESTIONNAIRE_OPTION = 'questionnaire';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var FeedbackQuestionnaire $questionnaire */
        $questionnaire = $options[self::QUESTIONNAIRE_OPTION];
        $questions     = [];

        foreach ($questionnaire->getQuestions() as $question) {
            $questions[] = [
                'name'  => 'question-' . $question->getUuid() . '-thesis',
                'label' => $question->getThesis(),
            ];
            if ($question->hasCounterThesis()) {
                $questions[] = [
                    'name'  => 'question-' . $question->getUuid() . '-counter-thesis',
                    'label' => $question->getCounterThesis(),
                ];
            }
        }
        shuffle($questions);

        foreach ($questions as $question) {
            $builder->add(
                $question['name'],
                LikertChoiceType::class,
                [
                    'label'    => $question['label'],
                    'expanded' => true,
                    'required' => true,
                ]
            );
        }

        $builder->add(
            'comment',
            TextareaType::class,
            [
                'label'    => 'Ergänzungen',
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::QUESTIONNAIRE_OPTION);
        $resolver->setAllowedTypes(self::QUESTIONNAIRE_OPTION, FeedbackQuestionnaire::class);

        $resolver->setDefaults(
            [
                'data_class' => FeedbackQuestionnaireFillout::class,
            ]
        );
    }
}
