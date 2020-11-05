<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Cache;


class FileCachePathGeneratorResizedImage implements FileCachePathGenerator
{

    /**
     * Resized with
     *
     * @var int
     */
    private $width;

    /**
     * Resized height
     *
     * @var int
     */
    private $height;


    /**
     * Resize mode
     *
     * @var string
     */
    private $mode;

    /**
     * Original filename
     *
     * @var string
     */
    private $name;

    /**
     * FileCachePathGeneratorResizedImage constructor.
     *
     * @param int    $width
     * @param int    $height
     * @param string $mode
     * @param string $name
     */
    public function __construct(int $width, int $height, string $mode, string $name)
    {
        $this->width  = $width;
        $this->height = $height;
        $this->mode   = $mode;
        $this->name   = $name;
    }

    /**
     * Get path, not prefixed by slash /
     *
     * @return string
     */
    public function getPath()
    {
        return sprintf(
            '%s_%s_%s/%s/%s',
            $this->width,
            $this->height,
            $this->mode,
            substr($this->name, 0, 2),
            substr($this->name, 2)
        );
    }

    /**
     * Get path
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getPath();
    }
}