<?php

namespace AppBundle\Twig\Extension;

/**
 * Twig extension for fast creation of glyphs
 *
 * Class BootstrapGlyph
 * @package AppBundle\Twig\Extension
 */
class BootstrapGlyph extends \Twig_Extension
{

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'glyph',
                array($this, 'bootstrapGlyph'),
                array('pre_escape' => 'html', 'is_safe' => array('html'))
            ),
        );

    }

    /**
     * Create html for a bootstrap glyph
     *
     * @param   string  $glyph      Glyph name
     * @return  string              Html bootstrap glyph snippet
     */
    public function bootstrapGlyph($glyph)
    {
        return sprintf('<span class="glyphicon glyphicon-%s" aria-hidden="true"></span>', $glyph);
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bootstrap_glyph';
    }
}
