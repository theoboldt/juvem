<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager;

use AppBundle\Juvimg\JuvimgService;
use AppBundle\UploadImage;
use Doctrine\Common\Cache\FilesystemCache;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

class UploadImageManager
{
    /**
     * Contains the cache instance
     *
     * @var FilesystemCache|null
     */
    protected $cache = null;

    /**
     * Contains the path where upload images are stored
     *
     * @var string
     */
    protected $uploadsDir;

    /**
     * Contains the name of the used upload mapping
     *
     * @var string
     */
    protected $uploadMapping;

    /**
     * JuvimgService if available
     *
     * @var JuvimgService|null
     */
    private $juvimgService;

    /**
     * Create new instance of upload image manager
     *
     * @param string             $cacheDir       Cache dir where modified images reside
     * @param array              $uploadMappings Available upload mappings
     * @param string             $mapping        Mapping to use
     * @param JuvimgService|null $juvimg         Juvimg Resize service if available
     */
    public function __construct($cacheDir, array $uploadMappings, $mapping, JuvimgService $juvimg = null)
    {
        $this->cache = new FilesystemCache($cacheDir);

        if (!array_key_exists($mapping, $uploadMappings)) {
            throw new \InvalidArgumentException('Could not find desired upload mapping in vich upload configuration');
        }
        $this->uploadMapping = $mapping;
        $this->uploadsDir    = $uploadMappings[$mapping]['upload_destination'];
        $this->juvimgService = $juvimg;
    }

    /**
     * Fetch image
     *
     * @param string   $name
     * @param null|int $width
     * @param null|int $height
     * @return UploadImage
     */
    public function fetch($name, $width = null, $height = null)
    {
        if ($width === null && $height === null) {
            return new UploadImage(
                $this->getOriginalImagePath($name)
            );
        } else {
            $this->fetchResized($name, $width, $height);
        }
    }

    /**
     * Fetch a resized image
     *
     * @param  string  $name    Image name
     * @param  integer $width   Width of image
     * @param  integer $height  Height of image
     * @param  string  $mode    Either ImageInterface::THUMBNAIL_INSET or ImageInterface::THUMBNAIL_OUTBOUND
     * @param  int     $quality JPG image quality applied when resizing
     * @return UploadImage
     */
    public function fetchResized($name, $width, $height, $mode = ImageInterface::THUMBNAIL_INSET, $quality = 70)
    {
        $key = $this->key($name, $width, $height, $mode);
        if (!$this->cache->contains($key)) {
            if ($this->juvimgService && $this->juvimgService->isAccessible()) {
                $result = $this->juvimgService->resize(
                    $this->getOriginalImagePath($name), $width, $height, $mode, $quality
                );
                $image = $result->getContents();
            } else {
                $imagine = new Imagine();
                $size    = new Box($width, $height);
                $image   = $imagine->open($this->getOriginalImagePath($name))
                                   ->thumbnail($size, $mode)
                                   ->get(
                                       $this->getOriginalImageType($name),
                                       [
                                           'jpeg_quality'          => $quality,
                                           'png_compression_level' => 9
                                       ]
                                   );
            }

            $this->cache->save($key, $image);
        }

        return new UploadImage($this->getOriginalImagePath($name), $this->cache->fetch($key));
    }

    /**
     * Get path to original uploaded file
     *
     * @param $name
     * @return string
     */
    public function getOriginalImagePath($name)
    {
        return $this->uploadsDir . '/' . $name;
    }

    /**
     * Get the type of an image
     *
     * @param string $name
     * @param bool   $asMimeType Set to true if the mime type should be returned and not jpg or png
     * @return string               Image type as mime type or png or jpg
     */
    public function getOriginalImageType($name, $asMimeType = false)
    {
        $path     = $this->getOriginalImagePath($name);
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);

        if ($asMimeType) {
            return $mimeType;
        }

        return ($mimeType == 'image/jpeg') ? 'jpg' : 'png';
    }

    public function key($name, $width, $height, $mode)
    {
        return sprintf('%s_%s_%s_%s_%s', $this->uploadMapping, $name, $width, $height, $mode);
    }
}