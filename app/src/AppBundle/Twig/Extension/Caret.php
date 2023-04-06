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

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for easily displaying carets
 *
 * @package AppBundle\Twig\Extension
 */
class Caret extends AbstractExtension
{

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'caret',
                [
                    $this,
                    'caret'
                ],
                [
                    'is_safe' => ['html']
                ]
            ),
            new TwigFunction(
                'caretRight',
                [
                    $this,
                    'caretRight'
                ],
                [
                    'is_safe' => ['html']
                ]
            ),
            new TwigFunction(
                'caretRightDouble',
                [
                    $this,
                    'caretRightDouble'
                ],
                [
                    'is_safe' => ['html']
                ]
            )
        ];
    }

    /**
     * @return string
     */
    public static function caret(): string
    {
        return '<span class="caret"></span>';
    }

    /**
     * @return string
     */
    public static function caretRight(): string
    {
        return '<span class="caret-right"></span>';
    }

    /**
     * @return string
     */
    public static function caretRightDouble(): string
    {
        return self::caretRight() . self::caretRight();
    }
}
