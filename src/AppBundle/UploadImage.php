<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle;

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
    protected $fileName;

    /**
     * Contains binary content of image
     *
     * @var string
     */
    protected $imageContent;

    /**
     * UploadImage constructor.
     *
     * @param string $fileName Path to original image
     * @param string $imageContent Binary Content of image
     */
    public function __construct($fileName, $imageContent = null)
    {
        $this->fileName     = $fileName;
        $this->imageContent = $imageContent;
    }

    /**
     * Determine if this image actually exists
     *
     * @return bool
     */
    public function exists() {
        return file_exists($this->fileName) && is_readable($this->fileName);
    }

    /**
     * Get modification Etag for file
     *
     * @return string
     */
    public function getETag()
    {
        return sha1($this->fileName);
    }

    /**
     * Get modification date for image
     *
     * @return \DateTime
     */
    public function getMTime()
    {
        return \DateTime::createFromFormat('U', filemtime($this->fileName));
    }

    /**
     * Get the image as binary output in selected format and dimension
     *
     * @return string
     */
    public function get()
    {
        if (!$this->imageContent) {
            $this->imageContent = file_get_contents($this->fileName);
        }

        return $this->imageContent;
    }

    /**
     * Get the type of an image
     *
     * @param bool $asMimeType Set to true if the mime type should be returned and not jpg or png
     * @return string               Image type as mime type or png or jpg
     */
    public function getType($asMimeType = false)
    {
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->fileName);

        if ($asMimeType) {
            return $mimeType;
        }

        return ($mimeType == 'image/jpeg') ? 'jpg' : 'png';
    }

}