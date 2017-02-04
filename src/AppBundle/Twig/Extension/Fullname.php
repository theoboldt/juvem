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

/**
 * Twig extension for fast creation of glyphs
 *
 * Class Fullname
 *
 * @package AppBundle\Twig\Extension
 */
class Fullname extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
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
            return User::fullname($nameLastOrUser->getNameLast(), $nameLastOrUser->getNameFirst());
        } else {
            return User::fullname($nameLastOrUser, $nameFirst);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'fullname';
    }
}
