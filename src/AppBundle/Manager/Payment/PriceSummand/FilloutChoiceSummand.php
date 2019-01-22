<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Payment\PriceSummand;


use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\Participant;

class FilloutChoiceSummand extends FilloutSummand implements SummandInterface, AttributeAwareInterface
{
    /**
     * Selected choice option
     *
     * @var AttributeChoiceOption
     */
    private $choice;

    /**
     * FilloutSummand constructor.
     *
     * @param SummandImpactedInterface $impacted
     * @param Fillout $fillout
     * @param float|int $value Numeric summand value in euro
     * @param AttributeChoiceOption $choice
     */
    public function __construct(
        SummandImpactedInterface $impacted, Fillout $fillout, $value, AttributeChoiceOption $choice
    )
    {
        $this->choice = $choice;
        parent::__construct($impacted, $fillout, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'Auswahlfeld';
    }

    /**
     * Get selected choice
     *
     * @return AttributeChoiceOption
     */
    public function getChoice()
    {
        return $this->choice;
    }
}
