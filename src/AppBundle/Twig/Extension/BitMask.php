<?php

namespace AppBundle\Twig\Extension;

use AppBundle\BitMask\BitMaskAbstract;
use AppBundle\BitMask\LabelFormatter;

/**
 * Twig extension for formatting bitmasks in twig
 *
 * Class BitMask
 *
 * @package AppBundle\Twig\Extension
 */
class BitMask extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'bitmask',
                array($this,
                      'formatBitMask'
                ),
                array('pre_escape' => 'html',
                      'is_safe'    => array('html')
                )
            ),
            new \Twig_SimpleFilter(
                'bitmaskoption',
                array($this,
                      'formatBitMaskOption'
                ),
                array('pre_escape' => 'html',
                      'is_safe'    => array('html')
                )
            )
        );

    }

    /**
     * Create html for a bootstrap glyph
     *
     * @param   string          $glue Glue between labels
     * @param   BitMaskAbstract $mask A bitmask to format
     * @param   LabelFormatter  $formatter A formatter if not a default formatter should be used
     * @return  string                      Html bootstrap glyph snippet
     */
    public function formatBitMask($glue, BitMaskAbstract $mask, LabelFormatter $formatter = null)
    {
        if (!$formatter) {
            $formatter = new LabelFormatter();
        }

        return $formatter->formatMask($mask, $glue);
    }


    /**
     * Create html for a bootstrap glyph
     *
     * @param   string          $option Option to format
     * @param   BitMaskAbstract $mask A bitmask to format
     * @param   LabelFormatter  $formatter A formatter if not a default formatter should be used
     * @return  string                      Html bootstrap glyph snippet
     */
    public function formatBitMaskOption($option, BitMaskAbstract $mask, LabelFormatter $formatter = null)
    {
        if (!$formatter) {
            $formatter = new LabelFormatter();
        }

        return $formatter->formatOption($mask, $option);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bitmask_format';
    }
}
