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

use AppBundle\Entity\HumanInterface;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for fast creation of glyphs
 *
 * Class ParticipationsParticipantsNamesGrouped
 *
 * @package AppBundle\Twig\Extension
 */
class ParticipationsParticipantsNamesGrouped extends AbstractExtension
{
    
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'participantsgrouped',
                [$this, 'participationParticipantsNamesGrouped'],
                ['pre_escape' => 'html', 'is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFilter(
                'humansgrouped',
                [$this, 'humanNamesGrouped'],
                ['pre_escape' => 'html', 'is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }
    
    /**
     * Get participant names grouped by last names
     *
     * @param Environment $env             Twig environment in order to escape names @see twig_escape_filter()
     * @param Participation $participation Participants of related participations are processed
     * @param bool $noHtml                 Default false, set to true to not use html markup
     * @return string
     */
    public function participationParticipantsNamesGrouped(
        Environment $env, Participation $participation, $noHtml = false
    )
    {
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
     * Get humans names grouped by last names
     *
     * @param Environment $env               Twig environment in order to escape names @see twig_escape_filter()
     * @param array|HumanInterface[] $people Humans which are to be  processed
     * @param bool $noHtml                   Default false, set to true to not use html markup
     * @return string
     */
    public function humanNamesGrouped(
        Environment $env, array $people, $noHtml = false
    )
    {
        /** @var Participant $participant */
        
        if ($noHtml) {
            $template = '%1$s';
        } else {
            $template = '<span title="%1$s %2$s" data-toggle="tooltip">%1$s</span>';
        }
        
        return self::combineNames(
            $people, $template, function ($value) use ($env) {
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
        /** @var array|Participant[] $participants */
        $participants = $participation->getParticipants()->toArray();
        return self::combineNames($participants, $template, $textFilter);
    }
    
    /**
     * Combine multiple human name and group by last names (for shorter texts)
     *
     * @param array|HumanInterface[] $people Humans to extract names
     * @param string $template               Template for each name
     * @param callable|null $textFilter      Filter to apply to first name and last name
     * @return string
     */
    public static function combineNames(array $people, string $template = '%1$s', callable $textFilter = null)
    {
        $groups = [];
        
        /** @var HumanInterface $human */
        foreach ($people as $human) {
            if (!$human instanceof HumanInterface) {
                throw new \InvalidArgumentException('Unsupported class transmitted');
            }
            $lastName = $human->getNameLast();
            if (is_callable($textFilter)) {
                $lastName = $textFilter($lastName);
            }
            $firstName = $human->getNameFirst();
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
