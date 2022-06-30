<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Feedback;

use AppBundle\Feedback\AnswerDistribution\QuestionAgreementAnswerDistribution;
use AppBundle\Feedback\AnswerDistribution\QuestionAnswerDistributionInterface;

class FeedbackQuestionnaireAnalyzer
{
    /**
     * @var FeedbackQuestionnaireFillout[]
     */
    private array $fillouts;

    /**
     * Decoded answer distribution
     *
     * @var array|null
     */
    private ?array $rawAnswerDistribution = null;

    /**
     * @var array|null
     */
    private ?array $answerDistribution = null;

    /**
     * @var FeedbackQuestionnaire
     */
    private FeedbackQuestionnaire $questionnaire;

    /**
     * @param FeedbackQuestionnaire          $questionnaire
     * @param FeedbackQuestionnaireFillout[] $fillouts
     */
    public function __construct(FeedbackQuestionnaire $questionnaire, array $fillouts)
    {
        $this->fillouts      = $fillouts;
        $this->questionnaire = $questionnaire;
    }

    /**
     * Questions
     *
     * @return FeedbackQuestion[]
     */
    public function getQuestions(): array
    {
        return $this->questionnaire->getQuestions();
    }

    /**
     * @return array
     */
    private function getRawAnswerDistribution(): array
    {
        if (!$this->rawAnswerDistribution) {
            $this->rawAnswerDistribution = [];
            foreach ($this->fillouts as $fillout) {
                /** @var FeedbackQuestionnaireAnswer $answer */
                foreach ($fillout->getAnswers() as $answer) {
                    if ($answer->getAnswer() === null) {
                        continue;
                    }

                    if (!isset($this->rawAnswerDistribution[$answer->getName()])) {
                        $this->rawAnswerDistribution[$answer->getName()] = [];
                    }
                    if (!isset($this->rawAnswerDistribution[$answer->getName()][$answer->getAnswer()])) {
                        $this->rawAnswerDistribution[$answer->getName()][$answer->getAnswer()] = 0;
                    }
                    $this->rawAnswerDistribution[$answer->getName()][$answer->getAnswer()]++;
                }
            }
        }
        return $this->rawAnswerDistribution;
    }

    /**
     * Get all answer distribution details
     *
     * @return QuestionAgreementAnswerDistribution[]
     */
    public function getQuestionAnswerDistributions(): array
    {
        if (!$this->answerDistribution) {
            $this->answerDistribution = [];
            $rawAnswerDistribution    = $this->getRawAnswerDistribution();
            foreach ($this->getQuestions() as $question) {
                switch ($question->getQuestionType()) {
                    case FeedbackQuestion::TYPE_AGREEMENT:
                        $this->answerDistribution[$question->getTopic()][$question->getUuid()]
                            = QuestionAgreementAnswerDistribution::createFromRawDistribution(
                            $question, $rawAnswerDistribution
                        );
                        break;
                    default:
                        throw new \RuntimeException(
                            'Unknown question type ' . $question->getQuestionType() . ' appeared'
                        );
                }
            } //foreach question
            ksort($this->answerDistribution);
            $questions = null;
            foreach ($this->answerDistribution as $topic => &$questions) {
                usort(
                    $questions,
                    function (QuestionAnswerDistributionInterface $a, QuestionAnswerDistributionInterface $b) {
                        return $a->getQuestion()->getTitle() <=> $b->getQuestion()->getTitle();
                    }
                );
            }
            unset($questions);
        }
        return $this->answerDistribution;
    }

    /**
     * Get answer distribution for requested question
     *
     * @param FeedbackQuestion $question
     * @return QuestionAgreementAnswerDistribution|null
     */
    public function getQuestionAnswerDistribution(FeedbackQuestion $question): ?QuestionAgreementAnswerDistribution
    {
        $this->getQuestionAnswerDistributions(); //ensure calculated
        return $this->answerDistribution[$question->getUuid()] ?? null;
    }

    /**
     * Get fillout comments
     *
     * @return string[]
     */
    public function getFilloutComments(): array
    {
        $comments = [];
        foreach ($this->fillouts as $fillout) {
            /** @var FeedbackQuestionnaireAnswer $answer */
            if ($fillout->getComment()) {
                $comments[] = $fillout->getComment();
            }
        }
        return $comments;
    }
}
