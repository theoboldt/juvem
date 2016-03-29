<?php

namespace AppBundle\Twig\Extension;

class BootstrapTableFilter extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'tableFilterButton',
                array($this,
                      'bootstrapTableFilterButton'
                ),
                array('pre_escape' => 'html',
                      'is_safe'    => array('html')
                )
            ),
        );
    }


    /**
     * Create html for a bootstrap glyph
     *
     * @param   string $glyph Glyph name
     * @return  string              Html bootstrap glyph snippet
     * @throws  \InvalidArgumentException   If transmitted glyph is not available
     */
    public function bootstrapTableFilterButton($glyph)
    {


        return sprintf('<span class="glyphicon glyphicon-%s" aria-hidden="true"></span>', $glyph);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bootstrap_table_filter';
    }


}
