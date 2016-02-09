<?php

namespace AppBundle\Twig\Extension;

/**
 * Twig extension for fast creation of glyphs
 *
 * Class BootstrapGlyph
 *
 * @package AppBundle\Twig\Extension
 */
class BootstrapGlyph extends \Twig_Extension
{

    /**
     * Contains the list of available glyphicons
     *
     * @var array
     */
    protected static $glyphiconList = array(
        'asterisk',
        'plus',
        'euro',
        'minus',
        'cloud',
        'envelope',
        'pencil',
        'glass',
        'music',
        'search',
        'heart',
        'star',
        'star-empty',
        'user',
        'film',
        'th-large',
        'th',
        'th-list',
        'ok',
        'remove',
        'zoom-in',
        'zoom-out',
        'off',
        'signal',
        'cog',
        'trash',
        'home',
        'file',
        'time',
        'road',
        'download-alt',
        'download',
        'upload',
        'inbox',
        'play-circle',
        'repeat',
        'refresh',
        'list-alt',
        'lock',
        'flag',
        'headphones',
        'volume-off',
        'volume-down',
        'volume-up',
        'qrcode',
        'barcode',
        'tag',
        'tags',
        'book',
        'bookmark',
        'print',
        'camera',
        'font',
        'bold',
        'italic',
        'text-height',
        'text-width',
        'align-left',
        'align-center',
        'align-right',
        'align-justify',
        'list',
        'indent-left',
        'indent-right',
        'facetime-video',
        'picture',
        'map-marker',
        'adjust',
        'tint',
        'edit',
        'share',
        'check',
        'move',
        'step-backward',
        'fast-backward',
        'backward',
        'play',
        'pause',
        'stop',
        'forward',
        'fast-forward',
        'step-forward',
        'eject',
        'chevron-left',
        'chevron-right',
        'plus-sign',
        'minus-sign',
        'remove-sign',
        'ok-sign',
        'question-sign',
        'info-sign',
        'screenshot',
        'remove-circle',
        'ok-circle',
        'ban-circle',
        'arrow-left',
        'arrow-right',
        'arrow-up',
        'arrow-down',
        'share-alt',
        'resize-full',
        'resize-small',
        'exclamation-sign',
        'gift',
        'leaf',
        'fire',
        'eye-open',
        'eye-close',
        'warning-sign',
        'plane',
        'calendar',
        'random',
        'comment',
        'magnet',
        'chevron-up',
        'chevron-down',
        'retweet',
        'shopping-cart',
        'folder-close',
        'folder-open',
        'resize-vertical',
        'resize-horizontal',
        'hdd',
        'bullhorn',
        'bell',
        'certificate',
        'thumbs-up',
        'thumbs-down',
        'hand-right',
        'hand-left',
        'hand-up',
        'hand-down',
        'circle-arrow-right',
        'circle-arrow-left',
        'circle-arrow-up',
        'circle-arrow-down',
        'globe',
        'wrench',
        'tasks',
        'filter',
        'briefcase',
        'fullscreen',
        'dashboard',
        'paperclip',
        'heart-empty',
        'link',
        'phone',
        'pushpin',
        'usd',
        'gbp',
        'sort',
        'sort-by-alphabet',
        'sort-by-alphabet-alt',
        'sort-by-order',
        'sort-by-order-alt',
        'sort-by-attributes',
        'sort-by-attributes-alt',
        'unchecked',
        'expand',
        'collapse-down',
        'collapse-up',
        'log-in',
        'flash',
        'log-out',
        'new-window',
        'record',
        'save',
        'open',
        'saved',
        'import',
        'export',
        'send',
        'floppy-disk',
        'floppy-saved',
        'floppy-remove',
        'floppy-save',
        'floppy-open',
        'credit-card',
        'transfer',
        'cutlery',
        'header',
        'compressed',
        'earphone',
        'phone-alt',
        'tower',
        'stats',
        'sd-video',
        'hd-video',
        'subtitles',
        'sound-stereo',
        'sound-dolby',
        'sound-5-1',
        'sound-6-1',
        'sound-7-1',
        'copyright-mark',
        'registration-mark',
        'cloud-download',
        'cloud-upload',
        'tree-conifer',
        'tree-deciduous'
    );

    /**
     * Check if the transmitted glyph is available
     *
     * @param   string $glyph Name of the glyph
     * @return  bool          True if glyph is useable
     */
    public static function isGlyhiconAvailable($glyph)
    {
        return in_array($glyph, self::$glyphiconList);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'glyph',
                array($this,
                      'bootstrapGlyph'
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
    public function bootstrapGlyph($glyph)
    {
        if (!self::isGlyhiconAvailable($glyph)) {
            throw new \InvalidArgumentException('Transmitted glyph is not available');
        }

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
