<?php

namespace AppBundle\Twig\Extension;

use Knp\Bundle\MarkdownBundle\Parser\ParserManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HeadingShiftingMarkdownTwigExtension extends AbstractExtension
{
    /**
     * parserManager
     *
     * @var ParserManager
     */
    private $parserManager;
    
    /**
     * HeadingShiftingMarkdownTwigExtension constructor.
     *
     * @param ParserManager $parserManager
     */
    public function __construct(ParserManager $parserManager)
    {
        $this->parserManager = $parserManager;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'markdown_shifted',
                [
                    $this,
                    'markdown_shifted'
                ],
                [
                    'pre_escape' => 'html',
                    'is_safe'    => ['html']
                ]
            ),
        ];
    }
    
    /**
     * Convert markdown to html, shift headings by specified amount of levels
     *
     * @param string $text Markdown text
     * @param int $shift   Increase headings by this factor
     * @return string
     */
    public function markdown_shifted($text, int $shift = 1)
    {
        $html = $this->parserManager->transform($text, null);
        
        $html = self::shiftHeadingTag($html, 5, $shift);
        $html = self::shiftHeadingTag($html, 4, $shift);
        $html = self::shiftHeadingTag($html, 3, $shift);
        $html = self::shiftHeadingTag($html, 2, $shift);
        $html = self::shiftHeadingTag($html, 1, $shift);
        
        return $html;
    }
    
    /**
     * Shift single heading, do not shift if output heading would be greater than 6
     *
     * @param string $html HTML to convert at
     * @param int $level   Input level
     * @param int $shift   Shift
     * @return string Output HTML
     */
    private function shiftHeadingTag(string $html, int $level, int $shift): string
    {
        if ($shift < 0 || $shift > 5) {
            throw new \InvalidArgumentException('Incorrect shift value: ' . $shift);
        }
        
        $shifted = $level + $shift;
        if ($level + $shift > 6) {
            $shifted = 6;
        }
        
        $html = str_replace('<h' . $level . '>', '<h' . $shifted . '>', $html);
        $html = str_replace('</h' . $level . '>', '</h' . $shifted . '>', $html);
        
        return $html;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'markdown_shifted';
    }
}
