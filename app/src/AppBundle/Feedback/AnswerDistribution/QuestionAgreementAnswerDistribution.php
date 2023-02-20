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
     * @var AgreementAnswerDistribution|null
     */
    private ?AgreementAnswerDistribution $distributionCounterThesis;

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

        if ($question->hasCounterThesis()) {
            $distributionCounterThesis = new AgreementAnswerDistribution(
                $distribution['counter-thesis'][LikertChoiceType::DISAGREEMENT_FULL],
                $distribution['counter-thesis'][LikertChoiceType::DISAGREEMENT_PARTIAL],
                $distribution['counter-thesis'][LikertChoiceType::AGREEMENT_NEUTRAL],
                $distribution['counter-thesis'][LikertChoiceType::AGREEMENT_PARTIAL],
                $distribution['counter-thesis'][LikertChoiceType::AGREEMENT_FULL],
            );
        } else {
            $distributionCounterThesis = null;
        }

        return new self($question, $distributionThesis, $distributionCounterThesis);
    }

    /**
     * @param FeedbackQuestion                 $question
     * @param AgreementAnswerDistribution      $distributionThesis
     * @param AgreementAnswerDistribution|null $distributionCounterThesis
     */
    public function __construct(
        FeedbackQuestion             $question,
        AgreementAnswerDistribution  $distributionThesis,
        ?AgreementAnswerDistribution $distributionCounterThesis
    ) {
        $this->question                  = $question;
        $this->distributionThesis        = $distributionThesis;
        $this->distributionCounterThesis = $distributionCounterThesis;

        if ($distributionCounterThesis) {
            $this->distributionCombined = new AgreementAnswerDistribution(
                $distributionThesis->getDisagreementFull() + $distributionCounterThesis->getAgreementFull(),
                $distributionThesis->getDisagreementPartial() + $distributionCounterThesis->getAgreementPartial(),
                $distributionThesis->getNeutral() + $distributionCounterThesis->getNeutral(),
                $distributionThesis->getAgreementPartial() + $distributionCounterThesis->getDisagreementPartial(),
                $distributionThesis->getAgreementFull() + $distributionCounterThesis->getDisagreementFull(),
            );
        } else {
            $this->distributionCombined = clone $distributionThesis;
        }
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
     * @return AgreementAnswerDistribution|null
     */
    public function getDistributionCounterThesis(): ?AgreementAnswerDistribution
    {
        return $this->distributionCounterThesis;
    }

    /**
     * Determine if counter thesis distribution is present at all
     * 
     * @return bool
     */
    public function hasDistributionCounterThesis(): bool
    {
        return $this->distributionCounterThesis !== null;
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
    public function getThesisCounterThesisDeviationScore(): ?float
    {
        if (!$this->hasDistributionCounterThesis()) {
            return null;
        }
        $thesisDistribution        = $this->getDistributionThesis();
        $counterThesisDistribution = $this->getDistributionCounterThesis();
    }

}
