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

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ModalDialog extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter(
                'modalDialog',
                array($this,
                      'modalDialog'
                ),
                array('pre_escape'        => 'html',
                      'is_safe'           => array('html'),
                      'needs_environment' => true
                )
            ),
        );

    }
    
    /**
     * Create html for a bootstrap glyph
     *
     * @param Environment $twig
     * @return string Html bootstrap glyph snippet
     * @internal param string $glyph Glyph name
     */
    public function modalDialog(Environment $twig, $title)
    {
        $data = array('modalId'    => 'xxx',
                      'modalTitle' => $title,
                      'modalYes'   => 1,
                      'modalNo'    => 2
        );

        return $twig->render('modal/dialog.html.twig', $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'modal_dialog';
    }
}
