<?php

namespace AppBundle\Twig\Extension;

use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;

/**
 * Twig extension for fast creation of glyphs
 *
 * Class ParticipationsParticipantsNamesGrouped
 *
 * @package AppBundle\Twig\Extension
 */
class ParticipationsParticipantsNamesGrouped extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'participantsgrouped',
                [$this, 'participationParticipantsNamesGrouped'],
                ['pre_escape' => 'html', 'is_safe' => ['html']]
            ),
        ];

    }

    /**
     * Get participant names grouped by last names
     *
     * @param Participation $participation Participants of related participations are processed
     * @return string
     */
    public function participationParticipantsNamesGrouped(Participation $participation)
    {
        $groups = [];
        /** @var Participant $participant */

        foreach ($participation->getParticipants() as $participant) {
            $groups[$participant->getNameLast()][] = sprintf(
                '<span title="%1$s %2$s" data-toggle="tooltip">%1$s</span>', $participant->getNameFirst(), $participant->getNameLast()
            );
        }

        $groupsCombined  = [];
        foreach ($groups as $lastName => $firstNames) {
            $groupsCombined[] = implode(', ', $firstNames).' '.$lastName;
        }
        return implode(', ', $groupsCombined);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'participantsgrouped';
    }
}
