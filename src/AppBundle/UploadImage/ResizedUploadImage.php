<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\UploadImage;


class ResizedUploadImage extends UploadImage
{

    /**
     * Path to original file
     *
     * @var string
     */
    protected $originalPath;

    /**
     * UploadImage constructor.
     *
     * @param string $path         Path to image
     * @param string $originalPath Path to original image
     */
    public function __construct(string $path, string $originalPath)
    {
        $this->originalPath = $originalPath;
        parent::__construct($path);
    }

    /**
     * Get modification date of original image
     *
     * @return \DateTime
     */
    public function getMTime()
    {
        return \DateTime::createFromFormat('U', filemtime($this->originalPath));
    }

}