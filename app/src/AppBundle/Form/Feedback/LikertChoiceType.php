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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LikertChoiceType extends ChoiceType
{
    const LIKERT_NULL             = '';
    const LIKERT_NULL_LABEL       = 'keine Angabe';
    const LIKERT_NULL_LABEL_SHORT = 'k.A.';

    const DISAGREEMENT_FULL             = -2;
    const DISAGREEMENT_FULL_LABEL       = '…überhaupt nicht zu';
    const DISAGREEMENT_FULL_LABEL_SHORT = '--';

    const DISAGREEMENT_PARTIAL             = -1;
    const DISAGREEMENT_PARTIAL_LABEL       = '…nicht zu';
    const DISAGREEMENT_PARTIAL_LABEL_SHORT = '-';

    const AGREEMENT_NEUTRAL             = 0;
    const AGREEMENT_NEUTRAL_LABEL       = '…weder zu, noch nicht zu';
    const AGREEMENT_NEUTRAL_LABEL_SHORT = '0';

    const AGREEMENT_PARTIAL             = 1;
    const AGREEMENT_PARTIAL_LABEL       = '…teilweise zu';
    const AGREEMENT_PARTIAL_LABEL_SHORT = '+';

    const AGREEMENT_FULL             = 2;
    const AGREEMENT_FULL_LABEL       = '…völlig zu';
    const AGREEMENT_FULL_LABEL_SHORT = '++';

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices               = [
            self::DISAGREEMENT_FULL_LABEL    => self::DISAGREEMENT_FULL,
            self::DISAGREEMENT_PARTIAL_LABEL => self::DISAGREEMENT_PARTIAL,
            self::AGREEMENT_NEUTRAL_LABEL    => self::AGREEMENT_NEUTRAL,
            self::AGREEMENT_PARTIAL_LABEL    => self::AGREEMENT_PARTIAL,
            self::AGREEMENT_FULL_LABEL       => self::AGREEMENT_FULL,
        ];
        $options['choices']    = $choices;
        $options['empty_data'] = $options['required'] ? self::LIKERT_NULL_LABEL : self::LIKERT_NULL;

        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'block_name'  => 'likert_choice',
                'expanded'    => true,
                'multiple'    => false,
                'choice_attr' => function ($choice, $key, $value) {
                    if ($choice === self::LIKERT_NULL) {
                        $title = self::LIKERT_NULL_LABEL;
                        $short = self::LIKERT_NULL_LABEL_SHORT;
                    } else {
                        switch ($choice) {
                            case self::DISAGREEMENT_FULL:
                                $title = self::DISAGREEMENT_FULL_LABEL;
                                $short = self::DISAGREEMENT_FULL_LABEL_SHORT;
                                break;
                            case self::DISAGREEMENT_PARTIAL:
                                $title = self::DISAGREEMENT_PARTIAL_LABEL;
                                $short = self::DISAGREEMENT_PARTIAL_LABEL_SHORT;
                                break;
                            case self::AGREEMENT_NEUTRAL:
                                $title = self::AGREEMENT_NEUTRAL_LABEL;
                                $short = self::AGREEMENT_NEUTRAL_LABEL_SHORT;
                                break;
                            case self::AGREEMENT_PARTIAL:
                                $title = self::AGREEMENT_PARTIAL_LABEL;
                                $short = self::AGREEMENT_PARTIAL_LABEL_SHORT;
                                break;
                            case self::AGREEMENT_FULL:
                                $title = self::AGREEMENT_FULL_LABEL;
                                $short = self::AGREEMENT_FULL_LABEL_SHORT;
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
