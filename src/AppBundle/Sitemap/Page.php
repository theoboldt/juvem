<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Sitemap;

/**
 * Class Page
 *
 * @package AppBundle
 */
class Page
{

    const CHANGEFREQ_ALWAYS  = 'always';
    const CHANGEFREQ_HOURLY  = 'hourly';
    const CHANGEFREQ_DAILY   = 'daily';
    const CHANGEFREQ_WEEKLY  = 'weekly';
    const CHANGEFREQ_MONTHLY = 'monthly';
    const CHANGEFREQ_YEARLY  = 'yearly';
    const CHANGEFREQ_NONE    = 'never';

    /**
     * Page location
     *
     * @var string
     */
    protected $loc;

    /**
     * Priority value
     *
     * @var double
     */
    protected $priority;

    /**
     * Changefreq
     *
     * @var string
     */
    protected $changefreq;

    /**
     * Last modified of entry
     *
     * @var \DateTime
     */
    protected $lastmod;

    /**
     * Possibly holds some image urls
     *
     * @var array
     */
    protected $images = [];

    /**
     * Create new instance, related modification time is dependend on transmitted path
     *
     * @param string $path
     * @param string $loc
     * @param float  $priority
     * @param string $changefreq
     * @return self
     */
    public static function createForFile($path, $loc, $priority = 0.5, $changefreq = self::CHANGEFREQ_WEEKLY) {
        if (file_exists($path) && is_readable($path)) {
            $lastmod = \DateTime::createFromFormat('U', filemtime($path));
        } else {
            $lastmod    = new \DateTime('2017-01-01');
        }
        return new self($loc, $priority, $lastmod, $changefreq);
    }

    /**
     * SitemapPage constructor.
     *
     * @param string    $loc
     * @param float     $priority
     * @param string    $changefreq
     * @param \DateTime $lastmod
     */
    public function __construct($loc, $priority = 0.5, \DateTime $lastmod = null, $changefreq = self::CHANGEFREQ_WEEKLY)
    {
        $this->loc        = $loc;
        $this->priority   = $priority;
        $this->changefreq = $changefreq;
        $this->lastmod    = $lastmod ? $lastmod : new \DateTime('2017-01-01');
    }

    /**
     * Get @see $loc
     *
     * @return string
     */
    public function getLoc(): string
    {
        return $this->loc;
    }

    /**
     * Get @see $priority as string
     *
     * @return string
     */
    public function getPriority(): string
    {
        return sprintf("%1.1f", $this->priority);
    }

    /**
     * Get @see $changefreq
     *
     * @return string
     */
    public function getChangefreq(): string
    {
        return $this->changefreq;
    }

    /**
     * Get @see $lastmod as @see \DateTime object
     *
     * @return \DateTime
     */
    public function getLastmod(): \DateTime
    {
        return $this->lastmod;
    }

    /**
     * Get all assigned images
     *
     * @return array
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Add an image to the list
     *
     * @param string $image
     * @return self
     */
    public function addImage($image): Page
    {
        $this->images[] = $image;
        return $this;
    }




}