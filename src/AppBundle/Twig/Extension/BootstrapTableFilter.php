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
            new \Twig_SimpleFilter(
                'tableFilterButtonTri',
                array($this,
                      'bootstrapTableFilterButtonTri'
                ),
                array('pre_escape' => 'html',
                      'is_safe'    => array('html')
                )
            ),
        );
    }
    /*
        public function initRuntime(\Twig_Environment $environment)
        {
            //$filters = $environment->getExtension('core')->getFilters();
        }
    */

    /**
     * Create html for a bootstrap table filter button including multiple options to be selected as filter
     *
     * @param   string  $property      The name of the filterable property
     * @param   integer $defaultOption Index of default option
     * @param   array   $options       List of options to select for filter
     * @param   bool    $useGlyph      Set to true to add the filter glyph
     * @return  string                 Html result
     * @throws  \InvalidArgumentException   If default option is not available or transmitted value not numeric
     */
    public function bootstrapTableFilterButton($property, $defaultOption, array $options, $useGlyph = false)
    {
        if (!is_numeric($defaultOption) || !isset($options[$defaultOption])) {
            throw new \InvalidArgumentException('Default option has to be index number of desired element');
        } else {
            $defaultOptionText = $options[$defaultOption]['title'];
        }

        $glyph = $useGlyph ? '<span class="glyphicon glyphicon-filter" aria-hidden="true"></span>' : '';

        $optionString = '';
        foreach ($options as $option) {
            if (is_array($option['value'])) {
                $optionSelector = '[' . implode(', ', $option['value']) . ']';
            } else {
                $optionSelector = $option['value'];
            }
            $optionString .= sprintf(
                "<li><a href=\"#\" data-filter=\"%s\">%s</a></li>\n",
                $optionSelector,
                $option['title']
            );
        }

        return sprintf(
            '
<div class="btn-group dropup" data-property="%1$s" data-default="%2$d">
    <button class="btn btn-default btn-xs dropdown-toggle" type="button" id="filter-%1$s"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        %3$s<span class="description">%4$s</span>
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" aria-labelledby="filter-%1$s">
        %5$s
    </ul>
</div>',
            $property,
            $defaultOption,
            $glyph,
            $defaultOptionText,
            $optionString
        );
    }


    /**
     * Shortcut to create a tri state filter button
     *
     * Shortcut to create a tri state filter button, which first state is 1, second is 0 and third is
     * both of them (0 and 1).
     *
     * @param   string  $property      The name of the filterable property
     * @param   integer $defaultOption Index of default option
     * @param   string  $title1        Title of first option for state 1
     * @param   string  $title0        Title of second option for state 0
     * @param   string  $title0and1    Title third option for state 0 and 1 as well
     * @param   bool    $useGlyph      Set to true to add the filter glyph
     * @return  string                 Html result
     * @see bootstrapTableFilterButton()
     */
    public function bootstrapTableFilterButtonTri(
        $property, $defaultOption, $title1, $title0, $title0and1, $useGlyph = false
    )
    {
        $options = array(
            array('title' => $title1, 'value' => 1),
            array('title' => $title0, 'value' => 0),
            array('title' => $title0and1, 'value' => array(0, 1)),
        );
        return $this->bootstrapTableFilterButton($property, $defaultOption, $options, $useGlyph);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bootstrap_table_filter';
    }


}
