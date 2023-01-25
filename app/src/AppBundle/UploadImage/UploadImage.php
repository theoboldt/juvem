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

/**
 * Class UploadImage
 *
 * @package AppBundle
 */
class UploadImage
{
    /**
     * Path to the file
     *
     * @var string
     */
    protected $path;

    /**
     * Create from File info
     *
     * @param \SplFileInfo $fileInfo
     * @return UploadImage
     */
    public static function createFromFileInfo(\SplFileInfo $fileInfo)
    {
        return new self($fileInfo->getPathname());
    }

    /**
     * UploadImage constructor.
     *
     * @param string $path Path to image
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Determine if this image actually exists
     *
     * @return bool
     */
    public function exists()
    {
        return file_exists($this->path) && is_readable($this->path);
    }

    /**
     * Get modification Etag for file
     *
     * @return string
     */
    public function getETag()
    {
        return sha1($this->path);
    }

    /**
     * Get modification date for image
     *
     * @return \DateTime
     */
    public function getMTime()
    {
        if ($this->exists()) {
            return \DateTime::createFromFormat('U', filemtime($this->path));
        } else {
            return new \DateTime('2000-01-01');
        }
    }

    /**
     * Get the image path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the image resource
     *
     * @return resource
     */
    public function getResource()
    {
        $handle = fopen($this->path, 'r');
        return $handle;
    }

    /**
     * Get the type of an image
     *
     * @param bool $asMimeType Set to true if the mime type should be returned and not jpg or png
     * @return string               Image type as mime type or png or jpg
     */
    public function getType($asMimeType = false)
    {
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->path);

        if ($asMimeType) {
            return $mimeType;
        }

        return ($mimeType == 'image/jpeg') ? 'jpg' : 'png';
    }

}
