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

use AppBundle\Entity\HumanTrait;
use AppBundle\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for fast creation of glyphs
 *
 * Class Fullname
 *
 * @package AppBundle\Twig\Extension
 */
class Fullname extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new TwigFilter(
                'fullname',
                array($this,
                      'fullname'
                )
            ),
        );

    }

    /**
     * Get template for full name
     *
     * @param string|HumanTrait $nameLastOrUser Instance of @see HumanTrait or last name as string
     * @param string|null       $nameFirst      First name as string or null if Instance of @see HumanTrait was
     *                                          transmitted as first argument
     * @return string Html bootstrap glyph snippet
     */
    public function fullname($nameLastOrUser, $nameFirst = null)
    {

        if (method_exists($nameLastOrUser, 'getNameLast') && method_exists($nameLastOrUser, 'getNameFirst')) {
            return User::generateFullname($nameLastOrUser->getNameLast(), $nameLastOrUser->getNameFirst());
        } else {
            return User::generateFullname($nameLastOrUser, $nameFirst);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'generateFullname';
    }
}
