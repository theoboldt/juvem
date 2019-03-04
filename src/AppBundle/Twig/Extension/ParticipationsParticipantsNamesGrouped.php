<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Twig\Extension;

use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Twig_Environment;

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
                ['pre_escape' => 'html', 'is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    /**
     * Get participant names grouped by last names
     *
     * @param Twig_Environment $env           Twig environment in order to escape names @see twig_escape_filter()
     * @param Participation    $participation Participants of related participations are processed
     * @param bool             $noHtml        Default false, set to true to not use html markup
     * @return string
     */
    public function participationParticipantsNamesGrouped(
        Twig_Environment $env, Participation $participation, $noHtml = false
    )
    {
        $groups = [];
        /** @var Participant $participant */

        if ($noHtml) {
            $template = '%1$s';
        } else {
            $template = '<span title="%1$s %2$s" data-toggle="tooltip">%1$s</span>';
        }
    
        return self::combinedParticipantsNames(
            $participation, $template, function ($value) use ($env) {
            return twig_escape_filter($env, $value);
        }
        );
    }
    
    /**
     * Combine multiple participants name and group by last names (for shorter texts)
     *
     * @param Participation $participation Participation to extract @see Participant from
     * @param string $template             Template for each name
     * @param callable|null $textFilter    Filter to apply to first name and last name
     * @return string
     */
    public static function combinedParticipantsNames(
        Participation $participation, string $template = '%1$s', callable $textFilter = null
    )
    {
        foreach ($participation->getParticipants() as $participant) {
            $lastName = $participant->getNameLast();
            if (is_callable($textFilter)) {
                $lastName = $textFilter($lastName);
            }
            $firstName = $participant->getNameFirst();
            if (is_callable($textFilter)) {
                $firstName = $textFilter($firstName);
            }
            
            $groups[$lastName][] = sprintf($template, $firstName, $lastName);
        }
        
        $groupsCombined = [];
        foreach ($groups as $lastName => $firstNames) {
            $groupsCombined[] = implode(', ', $firstNames) . ' ' . $lastName;
        }
        return implode('; ', $groupsCombined);
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'participantsgrouped';
    }
}
