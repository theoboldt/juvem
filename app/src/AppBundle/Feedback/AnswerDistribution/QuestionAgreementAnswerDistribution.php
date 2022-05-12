<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Feedback\AnswerDistribution;

use AppBundle\Feedback\FeedbackQuestion;
use AppBundle\Form\Feedback\LikertChoiceType;

class QuestionAgreementAnswerDistribution implements QuestionAnswerDistributionInterface
{
    /**
     * @var FeedbackQuestion
     */
    private FeedbackQuestion $question;

    /**
     * @var AgreementAnswerDistribution
     */
    private AgreementAnswerDistribution $distributionThesis;

    /**
     * @var AgreementAnswerDistribution
     */
    private AgreementAnswerDistribution $distributionCounterThesis;

    /**
     * @var AgreementAnswerDistribution
     */
    private AgreementAnswerDistribution $distributionCombined;

    /**
     * Create instance by raw distribution
     *
     * @param array $rawDistribution
     * @return static
     */
    public static function createFromRawDistribution(FeedbackQuestion $question, array $rawDistribution): self
    {
        $uuid         = $question->getUuid();
        $distribution = [
            'thesis'         => [
                LikertChoiceType::DISAGREEMENT_FULL    => 0,
                LikertChoiceType::DISAGREEMENT_PARTIAL => 0,
                LikertChoiceType::AGREEMENT_NEUTRAL    => 0,
                LikertChoiceType::AGREEMENT_PARTIAL    => 0,
                LikertChoiceType::AGREEMENT_FULL       => 0,
            ],
            'counter-thesis' => [
                LikertChoiceType::DISAGREEMENT_FULL    => 0,
                LikertChoiceType::DISAGREEMENT_PARTIAL => 0,
                LikertChoiceType::AGREEMENT_NEUTRAL    => 0,
                LikertChoiceType::AGREEMENT_PARTIAL    => 0,
                LikertChoiceType::AGREEMENT_FULL       => 0,
            ],
        ];
        foreach ($rawDistribution as $questionName => $questionDistribution) {
            if (preg_match(
                '/question-(?P<uuid>[0-9a-f\-]+)-(?P<class>thesis|counter-thesis)/', $questionName, $questionIdentifiers
            )) {
                if ($questionIdentifiers['uuid'] !== $uuid) {
                    continue;
                }
                foreach ($questionDistribution as $answer => $count) {
                    $distribution[$questionIdentifiers['class']][$answer] += $count;
                }
            } else {
                throw new \InvalidArgumentException('Unknwon question name ' . $questionName . ' occurred');
            }
        }

        $distributionThesis = new AgreementAnswerDistribution(
            $distribution['thesis'][LikertChoiceType::DISAGREEMENT_FULL],
            $distribution['thesis'][LikertChoiceType::DISAGREEMENT_PARTIAL],
            $distribution['thesis'][LikertChoiceType::AGREEMENT_NEUTRAL],
            $distribution['thesis'][LikertChoiceType::AGREEMENT_PARTIAL],
            $distribution['thesis'][LikertChoiceType::AGREEMENT_FULL],
        );

        $distributionCounterThesis = new AgreementAnswerDistribution(
            $distribution['counter-thesis'][LikertChoiceType::DISAGREEMENT_FULL],
            $distribution['counter-thesis'][LikertChoiceType::DISAGREEMENT_PARTIAL],
            $distribution['counter-thesis'][LikertChoiceType::AGREEMENT_NEUTRAL],
            $distribution['counter-thesis'][LikertChoiceType::AGREEMENT_PARTIAL],
            $distribution['counter-thesis'][LikertChoiceType::AGREEMENT_FULL],
        );

        return new self($question, $distributionThesis, $distributionCounterThesis);
    }

    /**
     * @param FeedbackQuestion            $question
     * @param AgreementAnswerDistribution $distributionThesis
     * @param AgreementAnswerDistribution $distributionCounterThesis
     */
    public function __construct(
        FeedbackQuestion            $question,
        AgreementAnswerDistribution $distributionThesis,
        AgreementAnswerDistribution $distributionCounterThesis
    ) {
        $this->question                  = $question;
        $this->distributionThesis        = $distributionThesis;
        $this->distributionCounterThesis = $distributionCounterThesis;

        $this->distributionCombined = new AgreementAnswerDistribution(
            $distributionThesis->getDisagreementFull() + $distributionCounterThesis->getDisagreementFull(),
            $distributionThesis->getDisagreementPartial() + $distributionCounterThesis->getDisagreementPartial(),
            $distributionThesis->getNeutral() + $distributionCounterThesis->getNeutral(),
            $distributionThesis->getAgreementPartial() + $distributionCounterThesis->getAgreementPartial(),
            $distributionThesis->getAgreementFull() + $distributionCounterThesis->getAgreementFull(),
        );
    }

    /**
     * @return FeedbackQuestion
     */
    public function getQuestion(): FeedbackQuestion
    {
        return $this->question;
    }

    /**
     * @return AgreementAnswerDistribution
     */
    public function getDistributionThesis(): AgreementAnswerDistribution
    {
        return $this->distributionThesis;
    }

    /**
     * @return AgreementAnswerDistribution
     */
    public function getDistributionCounterThesis(): AgreementAnswerDistribution
    {
        return $this->distributionCounterThesis;
    }

    /**
     * @return AgreementAnswerDistribution
     */
    public function getDistributionCombined(): AgreementAnswerDistribution
    {
        return $this->distributionCombined;
    }

    /**
     * @return float
     * @todo
     */
    public function getThesisCounterThesisDeviation(): float
    {
        throw new \Exception('Todo');
        $thesisDistribution        = $this->getThesisDistribution();
        $counterThesisDistribution = $this->getCounterThesisDistribution();
    }

}
