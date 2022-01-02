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

use AppBundle\Feedback\FeedbackManager;
use AppBundle\Feedback\FeedbackQuestionnaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportQuestionsType extends AbstractType
{
    const QUESTIONNAIRE_OPTION = 'questionnaire';

    private FeedbackManager $feedbackManager;

    /**
     * @param FeedbackManager $feedbackManager
     */
    public function __construct(FeedbackManager $feedbackManager)
    {
        $this->feedbackManager = $feedbackManager;
    }


    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $questions = $this->feedbackManager->provideQuestions();

        /** @var FeedbackQuestionnaire $questionnaire */
        $questionnaire          = $options[self::QUESTIONNAIRE_OPTION];
        $questionnaireQuestions = $questionnaire->getQuestions();

        foreach ($questions as $question) {
            foreach ($questionnaireQuestions as $questionnaireQuestion) {
                if ($question->getUuid() === $questionnaireQuestion->getUuid()
                    || $question->isSameAs($questionnaireQuestion)) {
                    continue 2;
                }
            }

            $builder
                ->add(
                    $question->getUuid(),
                    CheckboxType::class,
                    [
                        'label'    => $question->getThesis(),
                        'required' => false,
                    ]

                );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::QUESTIONNAIRE_OPTION);
        $resolver->setAllowedTypes(self::QUESTIONNAIRE_OPTION, FeedbackQuestionnaire::class);

        $resolver->setDefaults(
            [
            ]
        );
    }
}
